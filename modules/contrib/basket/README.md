# Add to cart

#### Hook the substitution of parameters included in the template button add
````
/**
 * Implements hook_basket_add_alter().
 * 
 */
function HOOK_basket_add_alter(&$info){

}
````

#### Hook replace the parameters included in the template popup after adding
````
/**
 * Implements hook_basket_add_popup_alter().
 * 
 */
function HOOK_basket_add_popup_alter(&$info){

}
````

#### Hook override the price of an item
````
/**
 * Implements hook_basket_getItemPrice_alter().
 * 
 */
function HOOK_basket_getItemPrice_alter(&$price, $row){

}
````

#### Override position discount
````
/**
 * Implements hook_basket_getItemDiscount_alter().
 * 
 */
function HOOK_basket_getItemDiscount_alter(&$discount, $row){

}
````

#### Hook override the picture of the element
````
/**
 * Implements hook_basket_getItemImg_alter().
 * 
 */
function HOOK_basket_getItemImg_alter(&$fid, $row){

}
````

#### Overriding the default value of the fields in the add button
````
/**
 * Implements hook_basket_add_field_views_defineOptions_alter().
 * 
 */
function HOOK_basket_add_field_views_defineOptions_alter(&$options){

}
````

#### Override field set in the add button
````
/**
 * Implements hook_basket_add_field_views_buildOptionsForm_alter().
 * 
 */
function HOOK_basket_add_field_views_buildOptionsForm_alter(&$form, $options){

}
````

#### Hook override total order value
````
/**
 * Implements hook_basket_getTotalSum_alter().
 * 
 */
function HOOK_basket_getTotalSum_alter(&$totalSum, $items, $notDiscount = FALSE, $notDelivety = FALSE){

}
````

#### Change data for payment
````
/**
 * Implements hook_basket_getPayInfo_alter().
 * 
 */
function HOOK_basket_getPayInfo_alter(&$payInfo){

}
````

#### Change current user currency
````
/**
 * Implements hook_basket_current_currency_alter().
 * 
 */
function HOOK_basket_current_currency_alter(&$currency){

}
````

#### Change attributes for count field
````
/**
 * Implements hook_basket_count_input_attr_alter().
 * 
 */
function HOOK_basket_count_input_attr_alter(&$attr, $entityID, $params = NULL){

}
````

#### Spoofing a request to receive a basket
````
/**
 * Implements hook_basket_getItemsInBasketQuery_alter().
 * 
 */
function HOOK_basket_getItemsInBasketQuery_alter(&$query){

}
````




# Params

#### Alter parameter definition
````
/**
 * Implements hook_basket_params_definition_alter().
 * 
 */
function HOOK_basket_params_definition_alter(&$element, $params, $isInline){

}
````

#### Validation of add to cart
````
/**
 * Implements hook_basketValidParams_alter().
 * 
 */
function HOOK_basketValidParams_alter(&$response, &$isValid, $post){

}
````




# Checkout

#### Hook replace default order values when creating
````
/**
 * Implements hook_basket_order_tokenDefaultValue_alter().
 * 
 */
function HOOK_basket_order_tokenDefaultValue_alter(&$node){

}
````

#### Hook for data retention to save when ordering
````
/**
 * Implements hook_basket_insertOrder_alter().
 * 
 */
function HOOK_basket_insertOrder_alter(&$basketOrderFields, &$basketItems, $entity){

}
````

#### Hook after ordering. Transmitted node and order ID
````
/**
 * Implements hook_basket_postInsertOrder_alter().
 * 
 */
function HOOK_basket_postInsertOrder_alter($entity, $orderId){

}
````

#### Ajax return hook
````
/**
 * Implements hook_basket_ajaxReloadDelivery_alter().
 * 
 */
function HOOK_basket_ajaxReloadDelivery_alter(&$response){

}
````

#### Change of form before initialization of delivery
````
/**
 * Implements hook_basket_delivery_preInit_alter().
 * 
 */
function HOOK_basket_delivery_preInit_alter(&$form, &$form_state){

}
````

#### Change the availability of a payment point
````
/**
 * Implements hook_basket_payment_option_access_alter().
 * 
 */
function HOOK_basket_payment_option_access_alter(&$access, $paymentKey, $form_state){

}
````

#### Change the availability of a delivery point
````
/**
 * Implements hook_basket_delivery_option_access_alter().
 * 
 */
function HOOK_basket_delivery_option_access_alter(&$access, $deliveryKey, $form_state){

}
````

#### Hook adding sub payment fields
````
/**
 * Implements hook_basketPaymentField_alter().
 * 
 */
function HOOK_basketPaymentField_alter(&$form, $form_state){

}
````

#### Hook the order number format
````
/**
 * Implements hook_basket_order_get_id_alter().
 * 
 */
function HOOK_basket_order_get_id_alter(&$orderID){

}
````




# Material type

#### Hook control additional fields types of materials
````
/**
 * Implements hook_basket_node_type_extra_fields_form_alter().
 * 
 */
function HOOK_basket_node_type_extra_fields_form_alter(&$form, $form_state){

}
````

#### Hook output data on additional fields of material
````
/**
 * Implements hook_basket_node_type_extra_fields_list_alter().
 * 
 */
function HOOK_basket_node_type_extra_fields_list_alter(&$field, $setting, $key){

}
````

#### Hook of redefinition of the request for determining the price of goods
````
/**
 * Implements hook_basketNodeGetPriceField_alter().
 * 
 * Return fields:'
 * - nid
 * - price
 * - currency
 * - priceConvert
 * - priceConvertOld
 */
function HOOK_basketNodeGetPriceField_alter(&$queryPrice, $nodeTypes = [], $nid = NULL){

}
````

#### Hook for changing the list of actions with a product
````
/**
 * Implements hook_stockProductLinks_alter().
 * 
 */
function HOOK_stockProductLinks_alter(&$links, $entity){

}
````




# Admin page

#### Hook override the pages of the admin box of the basket
````
/**
 * Implements hook_basket_admin_page_alter().
 * 
 */
function HOOK_basket_admin_page_alter(&$element, $params){

}
````

#### Redefinition of the amount of new data in the admin panel
````
/**
 * Implements hook_basket_get_new_count_alter().
 * 
 */
function HOOK_basket_get_new_count_alter(&$count, $type){

}
````

#### List of order management links
````
/**
 * Implements hook_basket_order_links_alter().
 * 
 */
function HOOK_basket_order_links_alter(&$links, $order){

}
````

#### Hook triggered after updating the basket
````
/**
 * Implements hook_basketOrderUpdate_alter().
 * 
 */
function HOOK_basketOrderUpdate_alter(&$updateOrder, $orderOld){

}
````

#### Hook basket template tokens
````
/**
 * Implements hook_basketTemplateTokens_alter().
 * 
 */
function HOOK_basketTemplateTokens_alter(&$tokens, $templateKey){

}
````

#### Hook basket token value
````
/**
 * Implements hook_basketTokenValue_alter().
 * 
 */
function HOOK_basketTokenValue_alter(&$value, $tokenName, $params){

}
````

#### Substitution of information about the settings of the payment point
````
/**
 * Implements hook_payment_settings_info_alter().
 * 
 */
function HOOK_payment_settings_info_alter(&$items, $result){

}
````

#### Hook for changing the state of an item in an order
````
/**
 * Implements hook_basket_item().
 * 
 */
function HOOK_basket_item($item, $type){

}
````




# Public pages

#### Hook basket page overrides
````
/**
 * Implements hook_basket_pages_alter().
 * 
 */
function HOOK_basket_pages_alter(&$element, $page_type, $page_subtype){

}
````




# Text settings

#### The hook of adding texts from the rest of the modules to the basket text management system
````
/**
 * Implements hook_basket_translate_context_alter().
 * 
 */
function HOOK_basket_translate_context_alter(&$context){

}
````