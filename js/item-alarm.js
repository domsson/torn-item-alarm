class ItemAlarm
{
	constructor(item_id, api_key, api_url=null, cb=null, attr="data-itemalarm")
	{
		if (api_url)
		{
			this.api_url = api_url;
		}
		else
		{
			this.api_url  = "https://api.torn.com/v2/market/";
			this.api_url += "{item_id}?selections=itemmarket";
			this.api_url += "&key={api_key}";
			this.api_url += "&comment=itemalarm";
		}
	
		this.item_id  = item_id;
		this.api_key  = api_key;
		this.callback = cb;
		this.attr     = attr;

		this.item  = null;
		this.timer = null;

		this.ready = false;
		this.bargains = [];
		this.dity = false;
	}

	init()
	{
		this.init_item();
		if (!this.item)
		{
			console.error("ItemAlarm: couldn't init item");
			return false;
		}

		if (!this.callback)
		{
			console.warn("ItemAlarm: no callback function given");
		}

		this.ready = true;
		return true;
	}

	start()
	{
		if (!this.ready) return false;
		if (this.timer) this.stop();
		let handler = this.on_interval.bind(this);
		this.timer = setTimeout(handler, this.item.interval * 1000);
		return true;
	}

	stop()
	{
		if (!this.timer) return false;
		clearTimeout(this.timer);
		this.timer = null;
		return true;
	}

	is_running()
	{
		return this.timer ? true : false;
	}

	is_dirty()
	{
		return this.dirty;
	}

	init_item()
	{
		let item_element = this.find_element();
		if (!item_element) return false;

		this.item = {
			"id": this.item_id,
			"element": item_element
		};

		this.fetch_item_settings();
	}

	fetch_item_settings()
	{
		if (!this.item.element) return;
		this.item["alarm_price"] = this.to_num(this.get_attr(this.item.element, "alarm-price"));
		this.item["trade_price"] = this.to_num(this.get_attr(this.item.element, "trade-price"));
		this.item["interval"]    = this.to_num(this.get_attr(this.item.element, "interval"));
	}

	find_element()
	{
		if (!this.attr)    return false;
		if (!this.item_id) return false;
		let attr = this.attr;
		let id   = this.item_id;

		let query = `[${attr}-id="${id}"]`;
		return document.querySelector(query);
	}

	on_market_response(request)
	{
		if (!request) return;

		/*
		if (req.readyState == 4 && req.status == 200)
			handler(req.responseText);
		*/

		if (request.status >= 400)
		{
			this.process_api_error(request);
			return;
		}

		//if (request.readyState >= 3 && request.responseText)
		if (request.readyState == 4 && request.status == 200)
		{
			this.process_api_response(request.responseText);
		}
	}

	process_api_error(request)
	{
		// TODO
	}

	process_api_response(response)
	{
		if (!response) return;
		response = JSON.parse(response);

		if (!Object.hasOwn(response, "itemmarket")) return;
		if (!Object.hasOwn(response.itemmarket, "listings")) return;

		let market = this.process_listings(response.itemmarket.listings);

		let listings = market.listings;
		let amount = market.amount;

		let bargains = []; 
		let dirty = false;
		for (let listing of listings)
		{
			if (!this.bargains.includes(listing.id))
			{
				dirty = true;
			}

			bargains.push(listing.id);
		}
		this.bargains = [...bargains];
		this.dirty = dirty;

		this.callback(this.item, listings, amount, dirty);
	}

	process_listings(listings)
	{
		let result = { "listings": [], "amount": 0 };

		if (!listings)                { console.log("e1"); return result; }
		if (!Array.isArray(listings)) { console.log("e2"); return result; }
		if (!this.item.alarm_price)   { console.log("e3"); return result; }

		for (let listing of listings)
		{
			if (listing.price > this.item.alarm_price) break;
			result.listings.push(listing);
			result.amount += listing.amount;
		}

		return result;
	}

	on_interval()
	{
		// Make sure we have the newest settings
		this.fetch_item_settings();
	
		// Query the market
		this.query_market();

		if (this.item.interval <= 0)
		{
			this.stop();
			return;
		}

		// Start the timeout again
		let handler = this.on_interval.bind(this);
		this.timer = setTimeout(handler, this.item.interval * 1000);
	}

	query_market()
	{
		if (!this.callback) return;

		let handler = this.on_market_response.bind(this);
		let url = this.build_api_url();
		let req = new XMLHttpRequest();
		req.onreadystatechange = function() { handler(req); };
		req.open("GET", url, true); // true for asynchronous 
		req.send(null);
	}

	build_api_url()
	{
		return this.api_url
				.replace("{item_id}", this.item_id)
				.replace("{api_key}", this.api_key);
	}

	get_attr(ele, suffix="")
	{
		let attr = this.attr + (suffix ? "-" + suffix : "");
		return ele.hasAttribute(attr) ? ele.getAttribute(attr) : null;
	}

	/*
	 * Convert the input value to an integer or float if it is numeric.
	 */
	to_num(val, int=false)
	{
		return isNaN(val) ? val : (int ? parseInt(val) : parseFloat(val));
	}

}

