from fastapi import FastAPI
from pydantic import BaseModel

app = FastAPI()

class Item(BaseModel):
    url: str

@app.post("/scrape")
async def scrape(item: Item):
    print(f"Scraping URL: {item.url}")

    return {
        "status": "success",
        "url": item.url,
        "price": "100",
        "title": "Test Product"
    }
