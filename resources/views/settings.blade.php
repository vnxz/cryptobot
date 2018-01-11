@extends('layouts.app')

@section('content')

<section id="settings">
    <form method="POST" action="/settings">
        {{ csrf_field() }}
        <div class="grid-container fluid">

            <div class="grid-x grid-padding-x align-center">

                <div class="form-container section-title cell">

                    <div class="grid-x grid-padding-x align-middle">

                        <div class="small-6 cell text-left">
                            <div class="align-vertical">
                                <h1>Settings</h1>
                            </div>
                        </div>

                        <div class="small-6 cell text-right align-self-middle">
                            <div class="align-vertical">
                                <button type="submit" class="button hollow" value="Update">Update</button>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="large-8 cell settings-area">
                    
                    <ul class="accordion" data-accordion data-multi-expand="true" data-allow-all-closed="true">
                        <li class="accordion-item" data-accordion-item>
                            <a href="#" class="accordion-title">Dashboard</a>
                            <div class="accordion-content" data-tab-content >

                                <div class="input-group">
                                    <span class="input-group-label">Fiat Currency</span>
                                    <select name="fiat" class="input-group-field"> 
                                        @if ($settings['fiat'] === 'usd')
                                            <option value="usd" selected="selected">USD</option>
                                        @else
                                             <option value="usd">USD</option>
                                        @endif
                                        @if ($settings['fiat'] === 'eur')
                                            <option value="eur" selected="selected">EUR</option>
                                        @else
                                            <option value="eur">EUR</option>
                                        @endif
                                    </select>
                                </div>

                            </div>
                        </li>
                        <li class="accordion-item" data-accordion-item>
                            <a href="#" class="accordion-title">Portfolio</a>
                            <div class="accordion-content" data-tab-content >
                                @empty ($portfolio) 
                                <span class="switch">
                                    <input class="switch-input" id="initialize-portfolio" type="checkbox" name="initialize_portfolio">
                                    <label class="switch-paddle" for="initialize-portfolio">
                                        <span class="show-for-sr">Initialize Portfolio</span>
                                        <span class="switch-active" aria-hidden="true">Yes</span>
                                        <span class="switch-inactive" aria-hidden="true">No</span>
                                    </label>
                                </span>
                                @endempty
                                @isset($portfolio)
                                <div class="input-group">
                                    <span class="input-group-label">Name</span>
                                    @if($portfolio->name === "") 
                                        <input name="portfolio_name" class="input-group-field" type="text"> 
                                    @else
                                        <input name="portfolio_name" class="input-group-field" type="text" value="{{$portfolio->name}}"> 
                                    @endif
                                </div>
                                <div class="input-group">
                                    <span class="input-group-label" >Counter Value Currency</span>
                                    <select name="portfolio_countervalue" class="input-group-field"> 
                                       
                                        @if ( $portfolio->counter_value === '')
                                            <option disabled value="" selected="selected">Select...</option>)
                                        @endif 
                                        @if ($portfolio->counter_value === 'usd')
                                            <option value="usd" selected="selected">USD</option>
                                        @else
                                             <option value="usd">USD</option>
                                        @endif
                                        @if ($portfolio->counter_value === 'eur')
                                            <option value="eur" selected="selected">EUR</option>
                                        @else
                                             <option value="eur">EUR</option>
                                        @endif

                                    </select>
                                </div>
                                @endisset

                            </div>
                        </li>
                        <li class="accordion-item" data-accordion-item>
                            <a href="#" class="accordion-title">Exchange APIs</a>
                            <div class="accordion-content" data-tab-content>

                                <div class="exchange-settings">
                                    <span class="h4">Bittrex</span>
                                    <span class="switch tiny api-switch float-right">
                                        @isset($exchanges_active['bittrex'])
                                        <input checked class="switch-input" id="bittrex-switch" type="checkbox" name="bittrex_switch">
                                        @endisset
                                        @empty($exchanges_active['bittrex'])
                                        <input class="switch-input" id="bittrex-switch" type="checkbox" name="bittrex_switch">
                                        @endempty
                                        <label class="switch-paddle" for="bittrex-switch">
                                        <span class="show-for-sr">Bittrex API</span>
                                        <span class="switch-active" aria-hidden="true">On</span>
                                        <span class="switch-inactive" aria-hidden="true">Off</span>
                                      </label>
                                    </span>
                                    <div class="input-group">
                                        <span class="input-group-label">Bittrex Key</span>
                                        <input class="input-group-field" type="text" name="bittrex_key" value="{{ $bittrex['bittrex_key'] }}">
                                    </div>

                                    <div class="input-group">
                                        <span class="input-group-label">Bittrex Secret</span>
                                        <input class="input-group-field" type="text" name="bittrex_secret" value="{{ $bittrex['bittrex_secret'] }}">
                                    </div>
                                    <div class="input-group">
                                        <span class="input-group-label">Bittrex Fee</span>
                                        <input class="input-group-field" type="text" name="bittrex_fee" value="{{ $bittrex['bittrex_fee'] }}">
                                    </div>
                                </div>
                                <div class="exchange-settings">
                                    <span class="h4">Bitstamp</span>
                                    <span class="switch tiny api-switch float-right">
                                        @isset($exchanges_active['bitstamp'])
                                        <input checked class="switch-input" id="bitstamp-switch" type="checkbox" name="bitstamp_switch">
                                        @endisset
                                        @empty($exchanges_active['bitstamp'])
                                        <input class="switch-input" id="bitstamp-switch" type="checkbox" name="bitstamp_switch">
                                        @endempty
                                        <label class="switch-paddle" for="bitstamp-switch">
                                        <span class="show-for-sr">Bitstamp API</span>
                                        <span class="switch-active" aria-hidden="true">On</span>
                                        <span class="switch-inactive" aria-hidden="true">Off</span>
                                      </label>
                                    </span>
                                    <div class="input-group">
                                        <span class="input-group-label">Bitstamp Key</span>
                                        <input class="input-group-field" type="text" name="bitstamp_key" value="{{ $bitstamp['bitstamp_key'] }}">
                                    </div>

                                    <div class="input-group">
                                        <span class="input-group-label">Bitstamp Secret</span>
                                        <input class="input-group-field" type="text" name="bitstamp_secret" value="{{ $bitstamp['bitstamp_secret'] }}">
                                    </div>

                                    <div class="input-group">
                                        <span class="input-group-label">Bitstamp Fee</span>
                                        <input class="input-group-field" type="text" name="bitstamp_fee" value="{{ $bitstamp['bitstamp_fee'] }}">
                                    </div>
                                </div>

                            </div>
                        </li>
                        
                    </ul>

                </div>

            </div>

        </div>
    </form>
</section>
@endsection