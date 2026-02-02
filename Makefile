# Telegram Bot Token
BOT_TOKEN := 8267358423:AAEFNyUTy34VMTSXYO0AqR1PDEfgEgkHhow
# API Base URL
TELEGRAM_API := https://api.telegram.org/bot$(BOT_TOKEN)
# Path to Sail (Docker wrapper)
SAIL := ./vendor/bin/sail

# ----------------------------------------------------------------------
# Command Shortcuts
# ----------------------------------------------------------------------

# Start ngrok on port 80
ngrok:
	# Starting ngrok tunnel...
	ngrok http 80 --log=stdout --host-header="rewrite"

# Check current webhook info
info:
	# Fetching webhook info...
	curl -s $(TELEGRAM_API)/getWebhookInfo | json_pp

# Set webhook url. Usage: make set url=https://your-url.ngrok-free.app
set:
	@if [ -z "$(url)" ]; then echo "Error: url argument is missing. Usage: make set url=https://..."; exit 1; fi
	# Setting webhook URL to $(url)...
	curl -F "url=$(url)" $(TELEGRAM_API)/setWebhook

# Start Laravel Horizon (via Sail)
horizon:
	# Starting Horizon inside Docker...
	$(SAIL) artisan horizon

# Start Queue Worker (via Sail)
worker:
	$(SAIL) artisan queue:work

# Start Laravel Scheduler (via Sail)
schedule:
	$(SAIL) artisan schedule:work

# Clear config and cache (via Sail)
flush:
	# Clearing cache...
	$(SAIL) artisan optimize:clear

# Run everything at once
dev:
	# Starting ngrok, horizon, and worker simultaneously...
	concurrently --kill-others \
	"make ngrok" \
	"make horizon" \
	"make worker" \
	"make schedule"
