# Orders

# DB structure:

This is oriented DB specification that will be for the `orders` and related entities. You are able to modify it.

orders {
	status enum
	amount_of_advance_payment_on_card decimal
	amount_of_advance_payment_via_terminal decimal
	amount_of_advance_payment_as_cash decimal
	amount_of_final_payment_on_card decimal
	amount_of_final_payment_via_terminal decimal
	amount_of_final_payment_as_cash decimal
	currency string
	created_at datetime
	updated_at datetime
	fully_payed_at datetime
	completed_at datetime
	id integer
	contact_id integer
	discount decimal
	completion_deadline datetime null def(null)
	total_price decimal
}

orders_items {
	id integer
	order_id integer
	item_id integer
	price_per_one_unit decimal
	currency string
	quantity integer
}

orders_services {
	id integer
	order_id integer
	price_per_one_unit integer
	currency integer
	quantity integer
	serviceid integer
	service_id integer
}

The core idea is that order will have a related items, related services and related contact. But contact id is the part of orders table.

# Consideration for frontend:

Follow the existing patterns of the system but be awere that when you create an `order`, inside it's creation form I should be able to dynamically select items (like in ItemMoveGroupComponent) that I want to include to a order. Items have their counts so when we do not have an items available (like out stock), user should be notified. Also I should be able to include a service to an order as many as I want - also dynamically selected. 

Also when creating an order I should be able to pick a contact for which I am creating the order. If there is no user available, I should be able create one in the popping up dialog window.
