<?php

namespace App\Listeners;

use App\Events\ConditionReached;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

use App\Notifications\ConditionalNotification;
use App\User;
use App\Conditional;
use App\Trade;
use App\Stop;
use App\Profit;
use App\Order;
use App\Library\Services\Facades\Bittrex;

class ExecuteConditional
{
    /**
     * Conditional order object
     * @var App\Conditional
     */
    protected $conditional;

    /**
     * Trade associated to the stop-loss
     * @var App\Trade
     */
    protected $trade;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  ConditionReached  $event
     * @return void
     */
    public function handle(ConditionReached $event)
    {
        // Get conditional to retrieve associated trade
        $this->conditional = $event->conditional;

        // Get price reached (condition)
        $price = $event->price;

        // Get trade linked to the conditional
        $this->trade = Trade::find($event->conditional->trade_id);

        // Destroy Conditional linked to the trade
        Conditional::destroy($event->conditional->id);

        // Launch a new order to the exchange according to the trade iformation
        $order = $this->newOrder($this->trade, $price);

        if ($order['status'] == 'success') {

            
            Log::notice('New Trade: Trade #' . $this->trade->id . ' opened at ' . $this->trade->exchange . ' for ' . $this->trade->pair . ' at ' . $this->trade->price . ' an amount of ' . $this->trade->amount . ' units for a total of ' . $this->trade->total .' with Stop-Loss at ' . $this->trade->stop_loss . ' and Take-Profit at ' . $this->trade->take_profit);

            $res = '#' . $this->trade->id . ' Trade Opened.' . 'Exchange: ' . $this->trade->exchange . ' Pair: ' . $this->trade->pair . ' Price: ' . $this->trade->price . ' Amount: ' . $this->trade->amount . ' Total: ' . $this->trade->total .' Stop-Loss: ' . $this->trade->stop_loss . ' Take-Profit: ' . $this->trade->take_profit;

            // NOTIFY: Conditional launched
            User::find($this->trade->user_id)->notify(new ConditionalNotification($this->trade));
        
            return response($res , 200)->header('Content-Type', 'text/plain');
       
        } else if ($order['status'] == 'fail') {

            // If order fails set trade status as 'Aborted'
            $this->trade->status = "Aborted";
            $this->trade->save();

            // Log ERROR: The trade couldn't be launched
            Log::error('New Trade: Trade #' . $this->trade->id . ' aborted due to ' . $order['message']);

            return response($order['message'], 500)->header('Content-Type', 'text/plain');
        }
    }

    /**
     * /Launch new order 
     * @param  string $exchange
     * @param  string $pair    
     * @param  float $price   
     * @param  float $amount  
     * @param  float $stop    
     * @param  float $profit  
     * @param  string $position
     * @return array         
     */
    private function newOrder($trade, $price) 
    {

        $stopLoss = new Stop;
        $takeProfit = new Profit;

        // Get the current user
        $user = User::find($trade->user_id);

        // Select Exchange
        switch ($exchange) {

            // BITTREX
            case 'bittrex':

                // Initialize Bittrex with user info
                Bittrex::setAPI($user->settings()->get('bittrex_key'), $user->settings()->get('bittrex_secret'));

                // Call to exchange API or a fakeOrder based on ENV->ORDERS_TEST
                if ( env('ORDERS_TEST', true) == true ) {

                    // TESTING SUCCESS
                    $order = FakeOrder::success();

                    // TESTING FAIL
                    // $order = FakeOrder::fail();
                    
                }
                else {

                    // Launch Bittrex sell order with Pair, Amount and Price as parameters
                    $order = Bittrex::buyLimit($trade->pair, $trade->amount, $price);
                    
                }


                // Check for order success
                if ($order->success == true) {

                    // Save exchange order id in the trade
                    $this->trade->order_id = $order->result->uuid;
                    $this->trade->save();

                    // If we get a success response we create an Order in our database to track
                    $orderToTrack = new Order;
                    $orderToTrack->user_id = $this->trade->user_id;
                    $orderToTrack->trade_id = $this->trade->id;
                    $orderToTrack->exchange = $this->trade->exchange;
                    $orderToTrack->order_id = $this->trade->order_id;
                    $orderToTrack->type = 'open';
                    $orderToTrack->save();

                    // EVENT: OrderLaunched
                    event(new OrderLaunched($orderToTrack));

                    // Return success if create order succeed
                    return ['status' => 'success', 'order_id' => $orderToTrack->order_id, 'stop_id' => $stopLoss->id, 'profit_id' => $takeProfit->id];

                }
                else {
                    / // If order fails set trade status as 'Aborted'
                    $trade->status = "Aborted";
                    $trade->save();
                    
                    return ['status' => 'fail', 'message' => $order->message];

                }

                break;
        }
    }
}
