<?php
class ControllerApiCron extends Controller {

    /**
     * @return void
     */
	public function checkOrdersShippingManager() {
        $collector_regions = collect(agconf('shipping_collector_regions'))->pluck('id')->toArray();
		$orders = \Agmedia\Models\Order\Order::query()->where('date_added', '>', \Illuminate\Support\Carbon::now()->subDays(10))->get();

        foreach ($orders as $order) {
            if ( ! in_array($order->shipping_zone_id, $collector_regions)) {
                if ($order->shipping_collector_id) {
                    \Agmedia\Features\Models\ShippingCollector::query()->where('shipping_collector_id', $order->shipping_collector_id)->decrement('collected');
                }

                $order->update([
                    'collect_date' => null,
                    'shipping_collector_id' => null
                ]);
            }
        }

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode(['success' => 200]));
	}
}
