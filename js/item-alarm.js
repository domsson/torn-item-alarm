class ItemAlarm
{
	constructor(item_id, api_key, cb=null, attr="data-itemalarm")
	{
		this.api_url  = "https://api.torn.com/market/";
		this.api_url += "{item_id}?selections=bazaar,itemmarket";
		this.api_url += "&key={api_key}";

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

		if (this.item.interval < 1)
		{
			this.item.interval = 1;
			console.warn("ItemAlarm: interval too small, set to 1");
		}

		this.ready = true;
		return true;
	}

	start()
	{
		if (!this.ready) return false;
		if (this.timer) this.stop();
		let handler = this.query_market.bind(this);
		this.timer = setInterval(handler, this.item.interval * 1000);
		return true;
	}

	stop()
	{
		if (!this.timer) return false;
		clearInterval(this.timer);
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

		let alarm_price = this.get_attr(item_element, "alarm-price");
		let trade_price = this.get_attr(item_element, "trade-price");
		let interval    = this.get_attr(item_element, "interval");

		this.item = {
			"id": this.item_id,
			"element": item_element,
			"alarm_price": this.to_num(alarm_price, true),
			"trade_price": this.to_num(trade_price, true),
			"interval": this.to_num(interval)
		};
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
		let log = "";
		log += "API response: ";
		log += "readyState = " + request.readyState;
		log += ", status = " + request.status;
		console.log(log);
		*/

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

		let bazaar = this.process_listings(response.bazaar);
		let market = this.process_listings(response.itemmarket);

		let listings = { "bazaar": bazaar.listings, "market": market.listings };
		let quantity = bazaar.quantity + market.quantity;

		let bargains = []; 
		let dirty = false;
		for (let listing of listings.bazaar)
		{
			if (!this.bargains.includes(listing.ID))
			{
				dirty = true;
			}

			bargains.push(listing.ID);
		}
		for (let listing of listings.market)
		{
			if (!this.bargains.includes(listing.ID))
			{
				dirty = true;
			}

			bargains.push(listing.ID);
		}
	this.bargains = [...bargains];
		this.dirty = dirty;

		this.callback(this.item, listings, quantity, dirty);
	}

	process_listings(listings)
	{
		let result = { "listings": [], "quantity": 0 };

		if (!listings)                return result;
		if (!Array.isArray(listings)) return result;
		if (!this.item.alarm_price)   return result;

		for (let listing of listings)
		{
			if (listing.cost > this.item.alarm_price) break;
			result.listings.push(listing);
			result.quantity += listing.quantity;
		}

		return result;
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

