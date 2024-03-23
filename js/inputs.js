class Inputs
{
	constructor(cfg)
	{
		// configuration with defaults
		this.config = {
			"attr": "data-inputs",
			"name": null,
			"event": "change", // default event type
			"callback": null,  // default callback function
			"context": null,   // user context object
			"sub": null,       // linked object
			"filter": null     // default filter function 
		};

		// merge user configuration
		Object.assign(this.config, cfg);

		// input elements
		this.inputs = {};

		// input specific callbacks
		this.callbacks = {};

		// input specific filters
		this.filters = {};
	}

	get attr()
	{
		return this.config.attr;
	}

	get name()
	{
		return this.config.name;
	}

	get context()
	{
		return this.config.context;
	}

	set context(ctx)
	{
		this.config.context = ctx;
	}

	get callback()
	{
		return this.config.callback;
	}

	set callback(cb)
	{
		this.config.callback = cb;
	}

	get sub()
	{
		return this.config.sub;
	}

	set sub(s)
	{
		this.config.sub = s;
	}

	get filter()
	{
		return this.config.filter;
	}

	set filter(f)
	{
		this.config.filter = f;
	}

	/*
	 * Perform initialization.
	 */
	init()
	{
		this.inputs = this.find_inputs();
		if (!this.inputs) return false;

		if (this.config.event) this.rig_inputs();
	}

	/*
	 * Find and return all input elements that belong to this instance.
	 */
	find_inputs(attr=this.config.attr, name=this.config.name)
	{
		if (!attr) return {};
		let inputs = {};
		let query = `[${attr}="${name ? name : ''}"]`;

		let all = document.querySelectorAll(query);
		for (let input of all)
		{
			let id = this.get_input_id(input); 
			if (!id) continue;
			inputs[id] = input;
		}
		return inputs;
	}

	/*
	 * Add event listeners to all input elements.
	 */
	rig_inputs()
	{
		let handler = this.on_event.bind(this);

		for (let id in this.inputs)
		{
			let input = this.inputs[id];
			let input_evt = this.get_input_attr(input, "event");
			let evt = input_evt ? input_evt : this.config.event;
			if (!evt) continue;
			input.addEventListener(evt, handler);
		}
	}

	/*
	 * Handle events.
	 */
	on_event(evt)
	{
		let input = evt.currentTarget;
		let id = this.get_input_id(input);
		let val = this.get_input_value(input);
		let details = {
			"event": evt,
			"input": input,
			"set": this.get_input_attr(input),
			"id": id,
			"value": val,
			"filtered": val,
			"instance": this,
			"context": this.config.context
		};

		this.trim(details);
		this.feed(details);
		this.call(details);
	}

	/*
	 * Set the 'filtered' value.
	 */
	trim(details)
	{
		let filter = this.filters[details.id] || this.config.filter;
		details.filtered = filter ? filter(details.value) : details.value;
	}

	/*
	 * Set a property on the subcriber according to the event.
	 */
	feed(details)
	{
		if (!this.sub) return;
		if (!this.sub.hasOwnProperty(details.id)) return;
		this.sub[details.id] = details.filtered;
	}

	feed_all(raw=false)
	{
		if (!this.sub) return;
		for (let id in this.inputs)
		{
			if (!this.sub.hasOwnProperty(id)) continue;
			this.sub[id] = this.get(id, raw); 
		}
	}

	grab_all()
	{
		if (!this.sub) return;
		for (let id in this.inputs)
		{
			if (!this.sub.hasOwnProperty(id)) continue;
			this.set(this.sub[id]); 
		}
	}

	/*
	 * Inform the relevant callback about an event.
	 */
	call(details)
	{
		let cb = this.callbacks[details.id] || this.config.callback;
		if (!cb) return;
		cb(details);
	}

	/*
	 * Fire a dummy event for all inputs.
	 */
	fire()
	{
		// dummy event
		let details = {
			"event": null,
			"input": null,
			"set": this.name,
			"id": null,
			"value": null,
			"filtered": null,
			"instance": this,
			"context": this.config.context
		};
		 
		for (let id in this.inputs)
		{
			details.id = id;
			details.input = this.inputs[id];
			details.value = this.get_input_value(details.input);
	
			this.trim(details);
			this.feed(details);
			this.call(details);
		}
	}

	/*
	 * Obtain the id/name of the input element, either from the data-inputs-id 
	 * attribute, or from the id or name property; in that order.
	 */
	get_input_id(input)
	{
		return this.get_input_attr(input, "id") || input.id || input.name || null;
	}

	/*
	 * Get an input element's value, possibly cast to a number or bool.
	 */
	get_input_value(input)
	{
		if (!input) return undefined;

		switch (input.type)
		{
			case "number":
			case "range":
			case "text":
			case "select-one":
				return this.to_num(input.value);
			case "select-multiple":
				return input.value.map(val => this.to_num(val));
			case "checkbox":
			case "radio":
				return input.checked;
			default:
				return input.value;
		}
	}

	/*
	 * Get an input element's data-inputs attribute value.
	 */
	get_input_attr(input, suffix="")
	{
		let attr = this.attr + (suffix ? "-" + suffix : "");
		return input.hasAttribute(attr) ? input.getAttribute(attr) : null;
	}

	/*
	 * Convert the input value to an integer or float if it is numeric.
	 */
	to_num(val, int=false)
	{
		return isNaN(val) ? val : (int ? parseInt(val) : parseFloat(val));
	}

	get_all(no_custom_cb=false, raw=false)
	{
		let vals = {};
		for (let id in this.inputs)
		{
			if (no_custom_cb && this.callbacks[id]) continue;
			vals[id] = this.get(id, raw);
		}
		return vals;
	}

	/*
	 * Get the current value from the input element.
	 */
	get(id, raw=false)
	{
		let input = this.inputs[id];
		return raw ? input.value : this.get_input_value(input);
	}

	set_all(vals, no_custom_cb=false)
	{
		for (let id in vals)
		{
			if (no_custom_cb && this.callbacks[id]) continue;
			this.set(id, vals[id]);
		}
	}

	/*
	 * Set the input element's current value.
	 */
	set(id, val)
	{
		let input = this.inputs[id];
		if (!input) return;
		input.value = val;
	}

	reg_callback(id, cb)
	{
		let input = this.inputs[id];
		if (!input) return;
		this.callbacks[id] = cb;
	}

	del_callback(id)
	{
		delete this.callbacks[id];
	}

	reg_filter(id, f)
	{
		let input = this.inputs[id];
		if (!input) return;
		this.filters[id] = f;
	}

	del_filter(id)
	{
		delete this.filters[id];
	}

	enable_all()
	{
		for (let id in this.inputs) this.enable(id);
	}

	enable(id)
	{
		let input = this.inputs[id];
		if (!input) return;
		input.removeAttribute("disabled");
	}

	disable_all()
	{
		for (let id in this.inputs) this.disable(id);
	}

	disable(id)
	{
		let input = this.inputs[id];
		if (!input) return;
		input.setAttribute("disabled", "");
	}
	
	/*
	 * Add an option to a 'select' input element.
	 */
	add_opt(id, val, txt)
	{
		let input = this.inputs[id];
		if (!input) return;

		let types = ["select-one", "select-multiple"];
		if (types.indexOf(input.type) < 0) return;
		
		let opt = document.createElement("option");
		opt.value = val;
		opt.text  = txt;
		input.add(opt);
	}
	
	/*
	 * Select an option of a 'select' input element.
	 * TODO 'select-multiple'
	 */
	sel_opt(id, val)
	{
		let input = this.inputs[id];
		if (!input) return;

		let types = ["select-one", "select-multiple"];
		if (types.indexOf(input.type) < 0) return;
		
		let options = input.querySelectorAll("option");
		for (let i = 0; i < options.length; ++i)
		{
			if (options[i].value == val) 
			{
				input.selectedIndex = i;
				break;
			}
		}
	}
}

