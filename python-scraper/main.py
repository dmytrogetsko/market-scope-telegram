from fastapi import FastAPI, HTTPException
from core.schemas import ScrapeRequest, ScrapeResponse
from core.olx_parser import OlxParser

app = FastAPI()
parser = OlxParser()

@app.post("/scrape", response_model=ScrapeResponse)
async def scrape_endpoint(req: ScrapeRequest):
    try:
        return await parser.parse(req.url)
    except Exception as e:
        print(f"CRITICAL ERROR: {e}")
        raise HTTPException(status_code=500, detail=str(e))
