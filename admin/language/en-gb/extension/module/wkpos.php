<?php
// Heading
$_['heading_title']    = 'Point of Sale (POS) System';

$_['text_module']           = 'Modules';
$_['text_success']          = 'Success: You have modified POS module!';
$_['text_edit']             = 'Edit POS Module';
$_['text_pos_front']        = 'POS Front End';
$_['text_default_customer'] = 'Default Customer Details';
$_['text_default_address']  = 'Default Customer Address';
$_['text_new_customer']     = 'New Customer Details';
$_['text_vertical']         = ' Vertical';
$_['text_horizontal']       = ' Horizontal';

// Entry
$_['entry_status']             = 'Status';
$_['entry_heading1']           = 'Heading on Login';
$_['entry_heading2']           = 'Sub-heading on Login';
$_['entry_logcontent']         = 'Login Content';
$_['entry_show_note']          = 'Show order note in invoice';
$_['entry_populars']           = 'No. of popular products';
$_['entry_low_stock']          = 'Quantity for low stock warning';
$_['entry_newsletter']         = 'Newsletter';
$_['entry_password']           = 'Default password';
$_['entry_customer_group']     = 'Customer Group';
$_['entry_firstname']          = 'First Name';
$_['entry_lastname']           = 'Last Name';
$_['entry_email']              = 'E-Mail';
$_['entry_telephone']          = 'Telephone';
$_['entry_fax']                = 'Fax';
$_['entry_company']            = 'Company';
$_['entry_address_1']          = 'Address 1';
$_['entry_address_2']          = 'Address 2';
$_['entry_city']               = 'City';
$_['entry_postcode']           = 'Postcode';
$_['entry_country']            = 'Country';
$_['entry_zone']               = 'Region / State';
$_['entry_store_country']      = 'Store Country';
$_['entry_store_zone']         = 'Store Region / State';
$_['entry_cash_status']        = 'Cash Status';
$_['entry_cash_title']         = 'Cash Title';
$_['entry_cash_order_status']  = 'Cash Complete Order Status';
$_['entry_card_status']        = 'Card Payment Status';
$_['entry_card_title']         = 'Card Payment Title';
$_['entry_card_order_status']  = 'Card Payment Complete Order Status';
$_['entry_discount_status']    = 'Discount Status';
$_['entry_coupon_status']      = 'Coupon Status';
$_['entry_tax_status']         = 'Tax Status';
$_['entry_store_logo']         = 'Show store logo';
$_['entry_store_name']         = 'Show store name';
$_['entry_store_address']      = 'Show store address';
$_['entry_order_date']         = 'Show order date';
$_['entry_order_time']         = 'Show order time';
$_['entry_order_id']           = 'Show order ID';
$_['entry_cashier_name']       = 'Show cashier name';
$_['entry_shipping_mode']      = 'Show shipping mode';
$_['entry_payment_mode']       = 'Show payment mode';
$_['entry_store_detail']       = 'Extra information (like TIN No., ST No.)';
$_['entry_barcode_width']      = 'Barcode Size';
$_['entry_barcode_type']       = 'Barcode Image Type';
$_['entry_barcode_name']       = 'Print product name with barcode';
$_['entry_email_agent']        = 'Send order e-mail to sales agent';
$_['entry_show_lowstock_prod'] = 'Show products with zero quantity in POS panel';
$_['entry_show_whole']         = 'Show whole product quantity online (<i>Important</i>)';

// Tab
$_['tab_general']      = 'General Settings';
$_['tab_customer']     = 'Customer Settings';
$_['tab_payment']      = 'Payment Settings';
$_['tab_receipt']      = 'Customise Receipt';
$_['tab_barcode']      = 'Barcode Settings';

// Help
$_['help_low_stock']     = 'This will be the maximum quantity for products to show the low stock warning';
$_['help_populars']      = 'This will be the maximum number of products that will be visible in popular products';
$_['help_heading1']      = 'This will be the primary heading on the login page';
$_['help_heading2']      = 'This will be the secondary heading on the login page';
$_['help_logcontent']    = 'This will be the content visible on the login page';
$_['help_cash_title']    = 'This will be the cash payment title at the POS panel';
$_['help_cash_status']   = 'This will be the order status after the payment is made by the cash';
$_['help_card_title']    = 'This will be the card payment title at the POS panel';
$_['help_card_status']   = 'This will be the order status after the payment is made by the card';
$_['help_store_detail']  = 'This field will be visible after the address in the receipt, you can add your store details like your TIN number, service tax number etc, each in new line';
$_['help_new_group']     = 'Select the customer group to which new customer will be added';
$_['help_newsletter']    = 'Select the newsletter status for new customers';
$_['help_password']      = 'This will be the default password for the customers that will be added from the POS panel';
$_['help_barcode_width'] = 'Enter the width of the barcode in pixels';
$_['help_show_whole']    = 'Select \'Yes\' if you want to show whole available product quantity on online store and select \'No\' if you want to show remaining product quanity on online store resulted from POS assignment. E.g. If you have 100 units of product 1, and you assign 80 units to outlet 1 then only 20 units will be available online.';

// Error
$_['error_warning']               = 'Warning: Please check the form carefully for errors!';
$_['error_permission']            = 'Warning: You do not have permission to modify POS module!';
$_['error_exists']                = 'Warning: E-Mail Address is already registered!';
$_['error_firstname']             = 'First Name must be between 1 and 32 characters!';
$_['error_lastname']              = 'Last Name must be between 1 and 32 characters!';
$_['error_email']                 = 'E-Mail Address does not appear to be valid!';
$_['error_telephone']             = 'Telephone must be between 3 and 32 characters!';
$_['error_password']              = 'Password must be between 4 and 20 characters!';
$_['error_confirm']               = 'Password and password confirmation do not match!';
$_['error_address_1']             = 'Address 1 must be between 3 and 128 characters!';
$_['error_city']                  = 'City must be between 2 and 128 characters!';
$_['error_postcode']              = 'Postcode must be between 2 and 10 characters for this country!';
$_['error_country']               = 'Please select a country!';
$_['error_zone']                  = 'Please select a region / state!';
$_['error_store_country']         = 'Please select a country for store!';
$_['error_store_zone']            = 'Please select a region / state for store!';
$_['error_custom_field']          = '%s required!';
$_['error_custom_field_validate'] = '%s invalid!';
// add new for
$_['error_cash_title']             = 'Cash Title must be between 3 and 64 characters!';
$_['error_card_title']             = 'Card Payment Title  must be between 3 and 64 characters!';
$_['error_low_stock']              = 'Low stock quantity must enter positive value!';
$_['error_wkpos_heading1']         = 'Heading on login  must be between 1 and 64 characters!';
