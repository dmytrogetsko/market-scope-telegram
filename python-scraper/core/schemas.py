from pydantic import BaseModel
from typing import List, Optional

# Input
class ScrapeRequest(BaseModel):
    url: str

# Listing Item
class ScrapedListing(BaseModel):
    id: str
    url: str
    title: str
    price: Optional[str] = None
    image: Optional[str] = None
    # description: Optional[str] = None  <-- will be added in future

# Response
class ScrapeResponse(BaseModel):
    page_title: Optional[str] = None
    listings: List[ScrapedListing] = []
