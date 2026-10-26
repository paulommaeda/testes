<?php
require '/wordpress/wp-load.php';
if (!defined('PMP_TESTING') || PMP_TESTING !== true) { http_response_code(404); exit; }
header('Content-Type: application/json');
update_option('woocommerce_coming_soon','no');
use Maeda\Perfis\{Credits,Commerce};
$orders=wc_get_orders(['limit'=>3,'type'=>'shop_order','orderby'=>'ID','order'=>'DESC']);$out=[];
foreach($orders as $order){if(!($order instanceof WC_Order))continue;if(!str_contains($order->get_billing_email(),'checkout.nortume.'))continue;$items=[];foreach($order->get_items() as $item)$items[]=['module'=>$item->get_meta('_pmp_module'),'size'=>(int)$item->get_meta('_pmp_pack_size'),'qty'=>$item->get_quantity()];$before=Credits::balances((int)$order->get_customer_id());$order->update_status('processing');Commerce::sync_order($order->get_id());$out[]=['id'=>$order->get_id(),'user'=>(int)$order->get_customer_id(),'items'=>$items,'before'=>$before,'after'=>Credits::balances((int)$order->get_customer_id())];}
echo wp_json_encode($out);
