<?php
// Heading
$_['heading_title']      = 'Unilateral Contract Termination and Return';

// Text
$_['text_account']       = 'Account';
$_['text_return']        = 'Unilateral Contract Termination and Return';
$_['text_return_detail'] = 'Request Details';
$_['text_description']   = '<p>Use this form to notify OPG Ružić that you are terminating the contract and to request a return of purchased items. A copy of the submitted request will be sent to your e-mail address.</p><p>Consumers may exercise their statutory right of withdrawal subject to applicable law and the store terms.</p>';
$_['text_order']         = 'Customer and Invoice Details';
$_['text_product']       = 'Items for Return';
$_['text_reason']        = 'Reason for Return';
$_['text_message']       = '<p>Your unilateral contract termination and return request has been received.</p><p>A copy has been sent to the e-mail address provided. OPG Ružić will contact you after review.</p>';
$_['text_return_id']     = 'Return ID:';
$_['text_order_id']      = 'Invoice Number:';
$_['text_date_ordered']  = 'Invoice Date:';
$_['text_status']        = 'Status:';
$_['text_date_added']    = 'Date Added:';
$_['text_comment']       = 'Return Comments';
$_['text_history']       = 'Return History';
$_['text_empty']         = 'You have not made any previous returns!';
$_['text_agree']         = 'I have read and agree to the <a href="%s" class="agree"><b>%s</b></a>';
$_['text_agree_fallback'] = 'I confirm that the submitted information is correct and that I am submitting a unilateral contract termination and return request.';
$_['text_return_products_title'] = 'Items you are returning';
$_['mail_return_admin_subject']    = '%s - new termination and return request #%s';
$_['mail_return_customer_subject'] = '%s - your request has been received #%s';
$_['mail_return_admin_intro']      = 'A new unilateral contract termination and return request has been submitted through the online form.';
$_['mail_return_customer_intro']   = 'We have received your unilateral contract termination and return request. Below is a copy of the submitted data.';
$_['mail_return_customer_footer']  = 'We will contact you after processing the request.';
$_['mail_return_label_return_id']  = 'Return request number';

// Column
$_['column_return_id']   = 'Return ID';
$_['column_order_id']    = 'Invoice Number';
$_['column_status']      = 'Status';
$_['column_date_added']  = 'Date Added';
$_['column_customer']    = 'Customer';
$_['column_product']     = 'Product Name';
$_['column_model']       = 'Model';
$_['column_quantity']    = 'Quantity';
$_['column_price']       = 'Price';
$_['column_opened']      = 'Opened';
$_['column_comment']     = 'Comment';
$_['column_reason']      = 'Reason';
$_['column_action']      = 'Action';

// Entry
$_['entry_order_id']     = 'Order ID';
$_['entry_date_ordered'] = 'Order Date';
$_['entry_invoice_number'] = 'Invoice Number';
$_['entry_invoice_date']   = 'Invoice Date';
$_['entry_firstname']    = 'First Name';
$_['entry_lastname']     = 'Last Name';
$_['entry_email']        = 'E-Mail';
$_['entry_telephone']    = 'Telephone';
$_['entry_product']      = 'Product Name';
$_['entry_model']        = 'Product Code';
$_['entry_product_code'] = 'Product Code';
$_['entry_quantity']     = 'Quantity';
$_['entry_price']        = 'Price';
$_['entry_reason']       = 'Reason for Return (optional)';
$_['entry_opened']       = 'Product is opened';
$_['entry_fault_detail'] = 'Note';
$_['entry_refund_iban']  = 'Refund IBAN (optional)';
$_['button_add_product'] = 'Add item';

// Error
$_['text_error']         = 'The returns you requested could not be found!';
$_['error_order_id']     = 'Invoice number required!';
$_['error_date_ordered'] = 'Enter a valid invoice date.';
$_['error_firstname']    = 'First Name must be between 1 and 32 characters!';
$_['error_lastname']     = 'Last Name must be between 1 and 32 characters!';
$_['error_email']        = 'E-Mail Address does not appear to be valid!';
$_['error_telephone']    = 'Telephone must be between 3 and 32 characters!';
$_['error_product']      = 'Product Name must be greater than 3 and less than 255 characters!';
$_['error_model']        = 'Product Model must be greater than 3 and less than 64 characters!';
$_['error_reason']       = 'You must select a return product reason!';
$_['error_return_products'] = 'Enter at least one item with a code, a quantity greater than 0, and a numeric price (comma or dot decimals).';
$_['error_refund_iban']     = 'Enter a valid IBAN or leave the field empty.';
$_['error_agree']        = 'Warning: You must agree to the %s!';
$_['error_agree_fallback'] = 'You must confirm your consent before submitting the request.';
