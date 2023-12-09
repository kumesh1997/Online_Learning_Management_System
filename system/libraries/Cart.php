<?php

defined('BASEPATH') OR exit('No direct script access allowed');


class CI_Cart {

	
	public $product_id_rules = '\.a-z0-9_-';

	
	public $product_name_rules = '\w \-\.\:';

	public $product_name_safe = TRUE;

	
	protected $CI;

	protected $_cart_contents = array();


	public function __construct($params = array())
	{
		// Set the super object to a local variable for use later
		$this->CI =& get_instance();

		// Are any config settings being passed manually?  If so, set them
		$config = is_array($params) ? $params : array();

		// Load the Sessions class
		$this->CI->load->driver('session', $config);

		// Grab the shopping cart array from the session table
		$this->_cart_contents = $this->CI->session->userdata('cart_contents');
		if ($this->_cart_contents === NULL)
		{
			// No cart exists so we'll set some base values
			$this->_cart_contents = array('cart_total' => 0, 'total_items' => 0);
		}

		log_message('info', 'Cart Class Initialized');
	}

	// --------------------------------------------------------------------

	/**
	 * Insert items into the cart and save it to the session table
	 *
	 * @param	array
	 * @return	bool
	 */
	public function insert($items = array())
	{
		// Was any cart data passed? No? Bah...
		if ( ! is_array($items) OR count($items) === 0)
		{
			log_message('error', 'The insert method must be passed an array containing data.');
			return FALSE;
		}
		$save_cart = FALSE;
		if (isset($items['id']))
		{
			if (($rowid = $this->_insert($items)))
			{
				$save_cart = TRUE;
			}
		}
		else
		{
			foreach ($items as $val)
			{
				if (is_array($val) && isset($val['id']))
				{
					if ($this->_insert($val))
					{
						$save_cart = TRUE;
					}
				}
			}
		}

		// Save the cart data if the insert was successful
		if ($save_cart === TRUE)
		{
			$this->_save_cart();
			return isset($rowid) ? $rowid : TRUE;
		}

		return FALSE;
	}

	// --------------------------------------------------------------------

	/**
	 * Insert
	 *
	 * @param	array
	 * @return	bool
	 */
	protected function _insert($items = array())
	{
		// Was any cart data passed? No? Bah...
		if ( ! is_array($items) OR count($items) === 0)
		{
			log_message('error', 'The insert method must be passed an array containing data.');
			return FALSE;
		}

		// --------------------------------------------------------------------

		// Does the $items array contain an id, quantity, price, and name?  These are required
		if ( ! isset($items['id'], $items['qty'], $items['price'], $items['name']))
		{
			log_message('error', 'The cart array must contain a product ID, quantity, price, and name.');
			return FALSE;
		}

		// --------------------------------------------------------------------

		// Prep the quantity. It can only be a number.  Duh... also trim any leading zeros
		$items['qty'] = (float) $items['qty'];

		// If the quantity is zero or blank there's nothing for us to do
		if ($items['qty'] == 0)
		{
			return FALSE;
		}

		
		if ( ! preg_match('/^['.$this->product_id_rules.']+$/i', $items['id']))
		{
			log_message('error', 'Invalid product ID.  The product ID can only contain alpha-numeric characters, dashes, and underscores');
			return FALSE;
		}

		if ($this->product_name_safe && ! preg_match('/^['.$this->product_name_rules.']+$/i'.(UTF8_ENABLED ? 'u' : ''), $items['name']))
		{
			log_message('error', 'An invalid name was submitted as the product name: '.$items['name'].' The name can only contain alpha-numeric characters, dashes, underscores, colons, and spaces');
			return FALSE;
		}

		// --------------------------------------------------------------------

		// Prep the price. Remove leading zeros and anything that isn't a number or decimal point.
		$items['price'] = (float) $items['price'];

	
		if (isset($items['options']) && count($items['options']) > 0)
		{
			$rowid = md5($items['id'].serialize($items['options']));
		}
		else
		{
		
			$rowid = md5($items['id']);
		}

	
		$old_quantity = isset($this->_cart_contents[$rowid]['qty']) ? (int) $this->_cart_contents[$rowid]['qty'] : 0;

		// Re-create the entry, just to make sure our index contains only the data from this submission
		$items['rowid'] = $rowid;
		$items['qty'] += $old_quantity;
		$this->_cart_contents[$rowid] = $items;

		return $rowid;
	}


	public function update($items = array())
	{
		// Was any cart data passed?
		if ( ! is_array($items) OR count($items) === 0)
		{
			return FALSE;
		}

		
		$save_cart = FALSE;
		if (isset($items['rowid']))
		{
			if ($this->_update($items) === TRUE)
			{
				$save_cart = TRUE;
			}
		}
		else
		{
			foreach ($items as $val)
			{
				if (is_array($val) && isset($val['rowid']))
				{
					if ($this->_update($val) === TRUE)
					{
						$save_cart = TRUE;
					}
				}
			}
		}

		// Save the cart data if the insert was successful
		if ($save_cart === TRUE)
		{
			$this->_save_cart();
			return TRUE;
		}

		return FALSE;
	}


	protected function _update($items = array())
	{
		// Without these array indexes there is nothing we can do
		if ( ! isset($items['rowid'], $this->_cart_contents[$items['rowid']]))
		{
			return FALSE;
		}

		// Prep the quantity
		if (isset($items['qty']))
		{
			$items['qty'] = (float) $items['qty'];
			// Is the quantity zero?  If so we will remove the item from the cart.
			// If the quantity is greater than zero we are updating
			if ($items['qty'] == 0)
			{
				unset($this->_cart_contents[$items['rowid']]);
				return TRUE;
			}
		}

		// find updatable keys
		$keys = array_intersect(array_keys($this->_cart_contents[$items['rowid']]), array_keys($items));
		// if a price was passed, make sure it contains valid data
		if (isset($items['price']))
		{
			$items['price'] = (float) $items['price'];
		}

		// product id & name shouldn't be changed
		foreach (array_diff($keys, array('id', 'name')) as $key)
		{
			$this->_cart_contents[$items['rowid']][$key] = $items[$key];
		}

		return TRUE;
	}


	protected function _save_cart()
	{
		// Let's add up the individual prices and set the cart sub-total
		$this->_cart_contents['total_items'] = $this->_cart_contents['cart_total'] = 0;
		foreach ($this->_cart_contents as $key => $val)
		{
			// We make sure the array contains the proper indexes
			if ( ! is_array($val) OR ! isset($val['price'], $val['qty']))
			{
				continue;
			}

			$this->_cart_contents['cart_total'] += ($val['price'] * $val['qty']);
			$this->_cart_contents['total_items'] += $val['qty'];
			$this->_cart_contents[$key]['subtotal'] = ($this->_cart_contents[$key]['price'] * $this->_cart_contents[$key]['qty']);
		}

		// Is our cart empty? If so we delete it from the session
		if (count($this->_cart_contents) <= 2)
		{
			$this->CI->session->unset_userdata('cart_contents');

			// Nothing more to do... coffee time!
			return FALSE;
		}

		// If we made it this far it means that our cart has data.
		// Let's pass it to the Session class so it can be stored
		$this->CI->session->set_userdata(array('cart_contents' => $this->_cart_contents));

		// Woot!
		return TRUE;
	}

	public function total()
	{
		return $this->_cart_contents['cart_total'];
	}

	// --------------------------------------------------------------------

	/**
	 * Remove Item
	 *
	 * Removes an item from the cart
	 *
	 * @param	int
	 * @return	bool
	 */
	 public function remove($rowid)
	 {
		// unset & save
		unset($this->_cart_contents[$rowid]);
		$this->_save_cart();
		return TRUE;
	 }

	// --------------------------------------------------------------------

	/**
	 * Total Items
	 *
	 * Returns the total item count
	 *
	 * @return	int
	 */
	public function total_items()
	{
		return $this->_cart_contents['total_items'];
	}

	// --------------------------------------------------------------------

	/**
	 * Cart Contents
	 *
	 * Returns the entire cart array
	 *
	 * @param	bool
	 * @return	array
	 */
	public function contents($newest_first = FALSE)
	{
		// do we want the newest first?
		$cart = ($newest_first) ? array_reverse($this->_cart_contents) : $this->_cart_contents;

		// Remove these so they don't create a problem when showing the cart table
		unset($cart['total_items']);
		unset($cart['cart_total']);

		return $cart;
	}

	// --------------------------------------------------------------------

	/**
	 * Get cart item
	 *
	 * Returns the details of a specific item in the cart
	 *
	 * @param	string	$row_id
	 * @return	array
	 */
	public function get_item($row_id)
	{
		return (in_array($row_id, array('total_items', 'cart_total'), TRUE) OR ! isset($this->_cart_contents[$row_id]))
			? FALSE
			: $this->_cart_contents[$row_id];
	}

	// --------------------------------------------------------------------

	/**
	 * Has options
	 *
	 * Returns TRUE if the rowid passed to this function correlates to an item
	 * that has options associated with it.
	 *
	 * @param	string	$row_id = ''
	 * @return	bool
	 */
	public function has_options($row_id = '')
	{
		return (isset($this->_cart_contents[$row_id]['options']) && count($this->_cart_contents[$row_id]['options']) !== 0);
	}

	// --------------------------------------------------------------------

	/**
	 * Product options
	 *
	 * Returns the an array of options, for a particular product row ID
	 *
	 * @param	string	$row_id = ''
	 * @return	array
	 */
	public function product_options($row_id = '')
	{
		return isset($this->_cart_contents[$row_id]['options']) ? $this->_cart_contents[$row_id]['options'] : array();
	}

	// --------------------------------------------------------------------

	/**
	 * Format Number
	 *
	 * Returns the supplied number with commas and a decimal point.
	 *
	 * @param	float
	 * @return	string
	 */
	public function format_number($n = '')
	{
		return ($n === '') ? '' : number_format( (float) $n, 2, '.', ',');
	}

	// --------------------------------------------------------------------

	/**
	 * Destroy the cart
	 *
	 * Empties the cart and kills the session
	 *
	 * @return	void
	 */
	public function destroy()
	{
		$this->_cart_contents = array('cart_total' => 0, 'total_items' => 0);
		$this->CI->session->unset_userdata('cart_contents');
	}

}
