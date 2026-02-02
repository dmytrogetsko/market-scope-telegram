import re
import logging
import sys
from playwright.async_api import async_playwright
from .schemas import ScrapeResponse, ScrapedListing

# Configure logging to stdout so Docker can capture it
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s [%(levelname)s] %(message)s',
    handlers=[logging.StreamHandler(sys.stdout)]
)
logger = logging.getLogger(__name__)

class OlxParser:
    def __init__(self):
        self.stop_words = ["куплю", "шукаю", "обмін", "broken", "не робочий"]
        logger.info(f"OlxParser initialized with stop-words: {self.stop_words}")

    def _extract_id(self, url: str) -> str:
        """Extracts ID from the URL"""
        match = re.search(r'-ID(\w+)\.html', url)
        return match.group(1) if match else str(hash(url))

    def _should_skip(self, title: str) -> bool:
        """Checks if the title contains any stop-words."""
        title_lower = title.lower()
        for word in self.stop_words:
            if word in title_lower:
                logger.info(f"⛔ SKIP: '{title}' because of stop-word: {word}")
                return True
        return False

    async def parse(self, url: str, existing_ids: list = None) -> ScrapeResponse:
        listings_data = []
        page_title = ""
        # Convert to set for O(1) lookups and ensure strings
        existing_ids = set(str(x) for x in (existing_ids or []))

        logger.info(f"🚀 Starting scrape for URL: {url}. Ignoring {len(existing_ids)} existing IDs.")

        async with async_playwright() as p:
            browser = await p.chromium.launch(headless=True, args=[
                '--no-sandbox',
                '--disable-setuid-sandbox',
                '--disable-dev-shm-usage',
                '--disable-accelerated-2d-canvas',
                '--disable-gpu'
            ])
            page = await browser.new_page()

            try:
                logger.info(f"Navigating to: {url}")
                await page.goto(url, timeout=60000, wait_until="domcontentloaded")

                try:
                    await page.wait_for_selector('[data-cy="l-card"]', timeout=10000)
                except:
                    logger.warning("Timeout waiting for cards.")
                    return ScrapeResponse(page_title="No results", listings=[])

                page_title = await page.title()
                cards = await page.query_selector_all('[data-cy="l-card"]')

                logger.info(f"Processing {len(cards)} cards...")

                for card in cards:
                    try:
                        # 1. Get ID and check if exists
                        item_id = await card.get_attribute("id")

                        if not item_id:
                            link_el = await card.query_selector("a")
                            if link_el:
                                href = await link_el.get_attribute("href")
                                if href:
                                    full_url = href if href.startswith("http") else f"https://www.olx.ua{href}"
                                    item_id = self._extract_id(full_url)

                        if item_id and str(item_id) in existing_ids:
                            continue

                        # 2. Parse details
                        title_el = await card.query_selector('[data-cy="ad-card-title"]')
                        if not title_el:
                            continue

                        title = (await title_el.inner_text()).strip()

                        if self._should_skip(title):
                            continue

                        link_el = await card.query_selector("a")
                        if not link_el:
                            continue

                        href = await link_el.get_attribute("href")
                        full_url = href if href.startswith("http") else f"https://www.olx.ua{href}"

                        if "olx.ua" not in full_url:
                            continue

                        # If item_id still not found, extract from final URL
                        if not item_id:
                            item_id = self._extract_id(full_url)
                            if str(item_id) in existing_ids:
                                continue

                        price_el = await card.query_selector('[data-testid="ad-price"]')
                        price = await price_el.inner_text() if price_el else None

                        img_el = await card.query_selector("img")
                        image_url = await img_el.get_attribute("src") if img_el else None

                        listings_data.append(ScrapedListing(
                            id=str(item_id),
                            url=full_url,
                            title=title,
                            price=price.strip() if price else None,
                            image=image_url
                        ))

                        logger.info(f"➕ Parsed: {title[:30]}... | {item_id}")

                    except Exception as e:
                        logger.error(f"Error parsing item: {e}")
                        continue

                logger.info(f"* Finished. Total items collected: {len(listings_data)}")

            except Exception as e:
                logger.critical(f"Global Scraping Error: {e}")
            finally:
                await browser.close()
                logger.info("Browser closed")

        return ScrapeResponse(page_title=page_title, listings=listings_data)
