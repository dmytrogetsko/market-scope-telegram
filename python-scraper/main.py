from fastapi import FastAPI
from pydantic import BaseModel
from typing import List, Optional

app = FastAPI()

# Input Model
class ScrapeRequest(BaseModel):
    url: str

# Listing Model (PHP: ScraperListingData)
class ScrapedListing(BaseModel):
    id: str
    url: str
    title: str
    price: Optional[str] = None
    image: Optional[str] = None

# Response Model (PHP: ScraperResponseData)
class ScrapeResponse(BaseModel):
    page_title: Optional[str] = None
    listings: List[ScrapedListing] = []

@app.post("/scrape", response_model=ScrapeResponse)
async def scrape(item: ScrapeRequest):
    print(f"Scraping URL: {item.url}")

    listing_mock = ScrapedListing(
        id="12345678",
        url=item.url,
        title="Sell Garage",
        price="1 200 $",
        image="https://olx.ua/image.jpg"
    )

    return ScrapeResponse(
        page_title="Оголошення OLX - Гаражі",
        listings=[listing_mock]
    )
