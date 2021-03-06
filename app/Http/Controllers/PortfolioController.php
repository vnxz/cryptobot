<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Events\PortfolioOpened;

use App\Library\Services\Broker;
use App\Library\Services\CoinGuru;

use App\User;
use App\Portfolio;
use App\PortfolioAsset;
use App\PortfolioOrigin;

class PortfolioController extends Controller
{

    public $user;

    public $portfolio;

    public $assets;

    public $origins;

    public $exchanges;

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
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        
        // Get current user
        $this->user = Auth::user();

        // Get exchanges
        $exchanges = $this->user->connections;
        if ($exchanges) $this->exchanges = $exchanges->pluck('exchange');
        else $this->exchanges = [];

        // Get user's portfolio
        $this->portfolio = Portfolio::where('user_id', $this->user->id)->first();

        if ($this->portfolio) {

            // Set update id in portfolio
            $this->portfolio->update_id = uniqid();
            $this->portfolio->save();

            // Get portfolio origins
            $this->origins = $this->portfolio->origins; 

            // DATA FOR MODALS (New Asset and New Origin)
            // Coin list
            $guru = new CoinGuru;
            $coins = $guru->getCoinList();

            $this->user->settings()->has('hideSmall') ? $hideSmall = $this->user->settings()->get('hideSmall') : $hideSmall = 'off';
            $this->user->settings()->has('minValue') ? $minValue = $this->user->settings()->get('minValue') : $minValue = 0.00000000;
            // Set origin types for new Portfolio Origins
            $originTypes = ['Online Wallet', 'Mobile Wallet', 'Desktop Wallet', 'Hardware Wallet', 'Paper Wallet'];

            return view('portfolio', ['originTypes' => json_encode($originTypes), 'exchanges' => json_encode($this->exchanges), 'portfolio' => $this->portfolio, 'origins' => $this->origins, 'coins' => json_encode($coins), 'hideSmall' => $hideSmall, 'minValue' => $minValue]);
        }
        else {
            return redirect('/settings');
        }
    }
    
    public function refresh() {
        // Get current user
        $this->user = Auth::user();

        // Get user's portfolio
        $this->portfolio = Portfolio::where('user_id', $this->user->id)->first();

        // Get portfolio origins
        $this->origins = $this->portfolio->origins; 

        // EVENT:  PortfolioOpened
        event(new PortfolioOpened($this->portfolio));

        return response("Portfolio loading.", 200)->header('Content-Type', 'text/plain');
    }

    public function updateAssets() {
       
        $guru = new CoinGuru;

        foreach ($this->assets as $asset) {
            $asset->balance =  $asset->amount * $guru->cryptocomparePriceGetSinglePrice($asset->symbol, "BTC")->BTC;

            $counterValue = strtoupper($this->portfolio->counter_value);
            $asset->counter_value = $asset->amount * $guru->cryptocomparePriceGetSinglePrice($asset->symbol, $counterValue)->$counterValue;
            $asset->save();
        }

    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function liveReload()
    {
        
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
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
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
