<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

use App\Library\FakeOrder;
use App\Notifications\TradeEditedNotification;
use App\Events\OrderLaunched;
use App\Events\ConditionalLaunched;
use App\Library\Services\Facades\Bittrex;
use App\Trade;
use App\Stop;
use App\Profit;
use App\Conditional;
use App\User;
use App\Order;

class TradeController extends Controller
{
    /**
     * Owner of the trade
     * @var User
     */
    protected $user;
  
    /**
     * Trade being controlled
     * @var Trade
     */
    protected $trade;
  
    /**
     * ID for the order launched by trade to the exchange
     * @var string
     */
    protected $order_id;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the trades dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try {
            // Double check for user to be authenticated
            if (Auth::check()) 
            {
                // Get current authenticated user
                $this->user = Auth::user();

                // Retrieve trade history
                $tradesHistory = Trade::where('user_id',  Auth::id())
                    ->where([
                        ['status', '=', 'Closed'] 
                    ])
                    ->orWhere([
                        ['status', '=', 'Cancelled']
                    ])
                    ->orWhere([
                        ['status', '=', 'Aborted']
                    ])
                    ->orderBy('updated_at', 'desc')
                    ->get();

                // Retrieve open trades
                $tradesOpened = Trade::where('user_id',  Auth::id())
                   ->orderBy('updated_at', 'desc')
                   ->get();

                $tradesOpened = $tradesOpened->reject(function ($trade) {
                    return $trade->status == 'Closed';
                });

                $tradesOpened = $tradesOpened->reject(function ($trade) {
                    return $trade->status == 'Waiting';
                });
                
                $tradesOpened = $tradesOpened->reject(function ($trade) {
                    return $trade->status == 'Cancelled';
                });

                $tradesOpened = $tradesOpened->reject(function ($trade) {
                    return $trade->status == 'Aborted';
                });

                // Retrieve waiting trades
                $tradesWaiting = Trade::where('user_id',  Auth::id())
                   ->where('status', 'Waiting')
                   ->orderBy('updated_at', 'desc')
                   ->get();

                // Return 'trades' view passing trade history and open trades objects
                return view('trades', ['tradesHistory' => $tradesHistory, 'tradesOpened' => $tradesOpened, 'tradesWaiting' => $tradesWaiting]);
            }
            else {

                // LOG: Not authorized
                Log::error("User not authorized trying to retieve trades.");

            }
        }catch(Exception $e) {

                // LOG: Exception trying to show trades
                Log::critical("[TradeController] Exception: " . $e->getMessage());

        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {   
        try {

            // Double check for user to be authenticated
            if ( Auth::check() ) 
            {
                // Create new Trade model
                $this->trade = new Trade;

                // Trade status change
                $this->trade->status = "Opening";

                // Fill the properties of the new trade that
                // are common to conditional and not conditional
                $this->trade->user_id = Auth::id();
                $this->trade->order_id = "-";
                $this->trade->stop_id = "-";
                $this->trade->profit_id = "-";
                $this->trade->condition_id = "-";
                $this->trade->position = $request->position;
                $this->trade->exchange = $request->exchange;
                $this->trade->pair = $request->pair;
                $this->trade->price = $request->price;
                $this->trade->amount = floatval($request->amount);
                $this->trade->total = $request->total;
                $this->trade->stop_loss = $request->stop_loss;
                $this->trade->take_profit = $request->take_profit;
                $this->trade->condition = $request->condition;
                $this->trade->condition_price = $request->condition_price;
                $this->trade->profit = 0.00000000;
                $this->trade->closing_price = 0.00000000;
                $this->trade->save();

                /**********************************
                 *  INMEDIATE TRADE
                 **********************************/
                if ( $this->trade->condition == "now" ) {

                    // Launch order to the exchange to get the order uuid
                    $order = $this->newOrder($this->trade);

                    if ( $order['status'] == 'success' ) {

                        // LOG: Order created
                        Log::info("[TradeController] Order #" . $this->trade->order_id . " created for Trade #" . $this->trade->id);

                        // Send the new trade to the client in json
                        return response($this->trade->toJson(), 200)->header('Content-Type', 'application/json');
                   
                    } else if ( $order['status'] == 'fail' ) {
                        
                        // LOG: Error creating order
                        Log::critical("[TradeController] Error creating Order for Trade #" . $this->trade->id . ": " . $order['message']);

                        // Trade status change
                        return response($order['message'], 500)->header('Content-Type', 'text/plain');
                    }
                }
                /**********************************
                 *  CONDITIONAL TRADE
                 **********************************/
                else {
                    
                    // Stores a conditional order in the database to watch
                    $conditional = $this->newConditional($this->trade);

                    if ( $conditional['status'] == 'success' ) {
                        
                        // LOG: Conditional order created
                        Log::info("[TradeController] Conditional order #" . $this->trade->condition_id . " created for Trade #" . $this->trade->id);

                        // Send the new trade to the client in json
                        return response($this->trade->toJson(), 200)->header('Content-Type', 'application/json');


                    } else if ( $conditional['status'] == 'fail' ) {

                        // LOG: Error creating order
                        Log::critical("[TradeController] Error creating conditional for Trade #" . $this->trade->id . ": " . $conditional['message']);

                        // Error creating conditional
                        return response($conditional['message'], 500 )->header( 'Content-Type', 'text/plain');
                    }
                }
            }
        } catch (Exception $e) {
            
            // LOG: Exception trying to create trade
            Log::critical("[TradeController] Exception: " . $e->getMessage());

            return response($e->getMessage(), 500)->header('Content-Type', 'text/plain');

        }
        
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($trade)
    {
        
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try {
            // Get the trade to edit
            $this->trade = Trade::find($id);

            // Update stop-loss and tale-profit
            $this->trade->stop_loss = $request->newStopLoss;
            $this->trade->take_profit = $request->newTakeProfit;
            $this->trade->save();

            // NOTIFY: Trade Edited
            User::find($this->trade->user_id)->notify(new TradeEditedNotification($this->trade));

            // Log NOTICE: Trade edited
            Log::notice("Trade #" . $this->trade->id . " edited. New Stop-Loss at " . $this->trade->stop_loss . " and new Take-Profit at " . $this->trade->take_profit);

        } catch (Exception $e) {

            // Log CRITICAL: Exception
            Log::critical("[TradeController] Exception: " . $e->message());

            // If exeption return error 500
            return response($e->message(), 500)->header('Content-Type', 'text/plain');

        }
        
    }

    
    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        try {

            // Get the trade to close
            $this->trade = Trade::find($id);


            /*****************************************
             *  CANCEL CONDITIONAL TRADE WAITING
             *****************************************/
            
            if ($this->trade->status == 'Waiting') {

                // Update trade status
                $this->trade->status = "Cancelling";
                $this->trade->save();

                // Set conditional to cancel
                $conditional = Conditional::find($this->trade->condition_id);
                $conditional->cancel = true;
                $conditional->save();

                // Send the cancelled trade to the client in json
                return response($this->trade->toJson(), 200)->header('Content-Type', 'application/json');
 
            }

            /*****************************************
             *  CLOSE OPENED TRADE
             *****************************************/
            else {

                // Update trade status
                $this->trade->status = "Closing";
                $this->trade->save();
                
                // Update stop-loss status if it exists
                $stop = Stop::find( $this->trade->stop_id);
                if ($stop) {
                    $stop->status = "Closing";
                    $stop->cancel = true;
                    $stop->save();
                }

                // Update take-profit status if it exists
                $profit = Profit::find( $this->trade->profit_id);
                if ($profit) {
                    $profit->status = "Closing";
                    $profit->cancel = true;
                    $profit->save();
                }

                // Get the user linked to the trade
                $user = User::find(Auth::id());
                
                // Select Exchange
                switch ($this->trade->exchange) {

                    // BITTREX
                    case 'bittrex':
                        
                        // Initialize Bittrex with user info
                        Bittrex::setAPI($user->settings()->get('bittrex_key'), $user->settings()->get('bittrex_secret'));
                        
                        // Check for order type
                        if ($this->trade->position == "long") {
           
                            // Call to exchange API or a fakeOrder based on ENV->ORDERS_TEST
                            if ( env('ORDERS_TEST', true) == true ) {

                                // TESTING SUCCESS
                                $remoteOrder = FakeOrder::success();

                                // TESTING FAIL
                                // $order = FakeOrder::fail();
                                
                            }
                            else {

                                // Launch Bittrex sell order with Pair, Amount and Price as parameters
                                $remoteOrder = Bittrex::sellLimit($this->trade->pair, $this->trade->amount, $request->closingprice);
                                
                            }
                            
                            // Check for remoteOrder success
                            if ($remoteOrder->success == true) {

                                // If we get a success response we create an Order in our database to track
                                $order = new Order;
                                $order->user_id = $this->trade->user_id;
                                $order->trade_id = $this->trade->id;
                                $order->exchange = 'bittrex';
                                $order->order_id = $remoteOrder->result->uuid;
                                $order->type = 'close';
                                $order->save();

                                // EVENT: OrderLaunched
                                event(new OrderLaunched($order));

                                // Log NOTICE: Order Launched
                                Log::notice("Order Launched: User action closing trade launched a SELL order (#" . $order->id .") at " . $request->closingprice  . " for trade #" . $this->trade->id . " for the pair " . $this->trade->pair . " at " . $this->trade->exchange);
                                
                                return response($this->trade->toJson(), 200)->header('Content-Type', 'application/json');

                            }
                            else {

                                // Log ERROR: Bittrex API returned error
                                Log::error("[TradeController] Bittrex API: " . $remoteOrder->message);

                                return response($remoteOrder->message, 500)->header('Content-Type', 'text/plain');

                            }

                        }
                        
                        break;

                }
                if ($this->trade->exchange == 'bittrex') {

                    

                }
        
            }
            
        } catch (Exception $e) {

            // Log CRITICAL: Exception
            Log::critical("[TradeController] Exception: " . $e->message());

            // If exeption return error 500
            return response($e->message(), 500)->header('Content-Type', 'text/plain');

        }
        

    }


    /**
     * Launch new order to the exchange
     * @param  trade $trade
     * @return array         
     */
    private function newOrder($trade) 
    {
        try {

            $stopLoss = new Stop;
            $takeProfit = new Profit;

            // Get the current user
            $user = User::find(Auth::id());


            switch ($trade->exchange) {

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
                        $order = Bittrex::buyLimit($trade->pair, $trade->amount, $trade->price);
                        
                    }
                   
                    // Check for order success
                    if ($order->success == true) {

                        // Save exchange order id in the trade
                        $this->trade->order_id = $order->result->uuid;
                        $this->trade->save();

                        // Create an Order in our database to track
                        $orderToTrack = new Order;
                        $orderToTrack->user_id = $trade->user_id;
                        $orderToTrack->trade_id = $trade->id;
                        $orderToTrack->exchange = $trade->exchange;
                        $orderToTrack->order_id = $trade->order_id;
                        $orderToTrack->type = 'open';
                        $orderToTrack->save();

                        // EVENT: OrderLaunched
                        event(new OrderLaunched($orderToTrack));

                        // Return success if create order succeed
                        return ['status' => 'success', 'order_id' => $orderToTrack->order_id, 'stop_id' => $stopLoss->id, 'profit_id' => $takeProfit->id];

                    }
                    else {
                        // // If order fails set trade status as 'Aborted'
                        $trade->status = "Aborted";
                        $trade->save();

                        // Return error if create order fails 
                        return ['status' => 'fail', 'message' => $order->message];

                    }

                    break;

            }
            
        } catch (Exception $e) {

            // Log CRITICAL: Exception
            Log::critical("[TradeController] Exception: " . $e->message());

            // If exeption return error 500
            return response($e->message(), 500)->header('Content-Type', 'text/plain');

        } 

    }


    /**
     * Stores a new Conditional order in conditionals table
     * @param  trade $trade
     * @return array        
     */
    private function newConditional($trade) 
    {   
        try {

            // Create a condition to be watched and lauched when reached
            $conditional = new Conditional;
            $conditional->user_id = Auth::id();
            $conditional->trade_id = $trade->id;
            $conditional->exchange = $trade->exchange;
            $conditional->pair = $trade->pair;
            $conditional->condition = $trade->condition;
            $conditional->condition_price = $trade->condition_price;
            $conditional->cancel = false;

            if ($conditional->save()) {

                 // Trade status change
                $trade->status = "Waiting";
                
                // Save conditional id into the trade
                $trade->condition_id = $conditional->id;
                $trade->save();

                // EVENT: ConditionalLaunched
                event(new ConditionalLaunched($conditional));

                return ["status"=>"success", "conditional_id"=>$conditional->id];

            }
            else {

                // If conditional fails set trade status as 'Aborted'
                $trade->status = "Aborted";
                $trade->save();
                
                return ["status"=>"fail", "message"=>"Error creating conditional trade."];

            }
            
        } catch (Exception $e) {

            // Log CRITICAL: Exception
            Log::critical("[TradeController] Exception: " . $e->message());

            // If exeption return error 500
            return response($e->message(), 500)->header('Content-Type', 'text/plain');

        } 

    }

}
