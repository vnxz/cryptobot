<template>
    <section id="portfolio-widget">

        <div class="grid-x grid-padding-x align-center">
            <div class="small-12 medium-10 cell">
                <div v-if="loadingPortfolio==true"  class="grid-container fluid">
                    <div class="grid-x grid-padding-x"> 
                        <div class="small-12 medium-shrink cell">
                            <div class="portfolio-loader">
                                <i class="fa fa-spinner fa-spin" aria-hidden="true"></i> Loading assets...
                            </div>
                        </div>
                    </div>
                </div>
                <div v-else class="grid-container fluid">
                    <div class="grid-x grid-padding-x"> 
                        <div class="small-12 medium-shrink cell">
                            <div class="counter-widget text-left">
                                <div class="title">Total BTC </div> <div class="counter"><span class="nowrap"><i class="fa fa-btc" aria-hidden="true"></i>{{ totalBtc }}</span></div>
                            </div>
                        </div>
                        <div class="small-12 medium-shrink cell">
                            <div class="counter-widget text-left">
                                 <div class="title">Total {{counterValueSymbol}} </div> <div class="counter"><span class="nowrap"><i :class="'fa fa-' + counterValueSymbol.toLowerCase()" aria-hidden="true"></i>{{ totalFiat}}</span></div>                  
                            </div>
                        </div>
                        <div class="small-6 medium-shrink cell">
                            <div class="counter-widget text-left">
                                  <div v-model="assetsCounter" class="title nowrap"># of Assets </div> <div class="counter">{{ assetsCounter }}</div>                  
                            </div>
                        </div>

                        <div class="small-6 medium-shrink cell">
                            <div class="counter-widget text-left">
                                  <div v-model="coinsCounter" class="title nowrap"># of Coins </div> <div class="counter">{{ coinsCounter }}</div>                  
                            </div>
                        </div>
                        <div class="small-6 medium-shrink cell">
                            <div class="counter-widget text-left">
                                 <div v-model="biggestGainer.symbol" class="title">Biggest Gainer </div> <div class="counter">{{ biggestGainer.symbol }} <span class="biggest profit"> (+{{ biggestGainer.profit }}%)</span></div>                  
                            </div>
                        </div>
                        <div class="small-6 medium-shrink cell">
                            <div class="counter-widget text-left">
                                  <div v-model="biggestLoser.symbol" class="title">Biggest Loser </div> <div class="counter">{{ biggestLoser.symbol }} <span class="biggest loss"> ({{ biggestLoser.profit }}%)</span></div>                  
                            </div>
                        </div>
                         <div class="small-12 medium-auto cell text-right">
                            <button class="button hollow button-refresh" v-on:click="refreshPortfolio()">
                               <span v-show="loadingPortfolio"><i class="fa fa-refresh fa-spin fa-fw" aria-hidden="true"></i> <span class="show-for-medium"> Loading...</span></span>
                               <span v-show="!loadingPortfolio"><i class="fa fa-refresh "></i> <span class="show-for-medium">Reload</span></span>
                            </button>
                        </div>
                        

                        

                    </div>
                </div>

                
            </div>

            <div class="small-12 medium-10 cell">
                <div class="portfolio-assets">
                    <table id="portfolioTable" class="display compact unstriped" width="100%"></table>
                </div>
            </div>

            <div class="small-12 medium-10 cell">
                <div class="grid-x grid-padding-x align-center-middle text-center dashboard">
                    <div class="small-12 large-6 cell charts">
                         <div v-show="showChart" class="ct-chart-totals ct-golden-section"></div>
                    </div>
                    <div class="small-12 large-6 cell charts">
                        <div v-show="showChart" class="ct-chart-origins ct-golden-section"></div>
                    </div>
                </div>
            </div>         
        </div>    
    </section>
    
</template>

<script>

export default {
    name: 'portfolio',
    props: [
    'portfolio',
    'hidesmall',
    'minvalue'
    ],
    data: () => {
        return {
            assets: [],
            hideSmallAssets: "no",
            minAssetValue: 0.00000000,
            totalBtc: 0,
            totalFiat: 0,
            totalExchanges: 0,
            biggestLoser: { symbol:"", profit: 0 },
            biggestGainer: { symbol:"", profit: 0 },
            assetsCounter: 0,
            coinsCounter: 0,
            portfolioAssetCount: 0,
            portfolioCurrentAssetCount: 0,
            showChart: false,
            uniqueAssetsBtc: [],
            uniqueAssetsFiat: [],
            uniqueAssetsName: [],
            uniqueAssetsOriginName: [],
            uniqueAssetsOriginFiat: [],
            mostValuableName: "",
            mostValuableValue: "",
            counterValueSymbol: '',
            portfolioTable: {},
            loadingPortfolio: false,
            loadingAsset: false,
            updatingAsset: false,
            chartistTotalsData: {labels: [], series: []},
            chartistTotalsChart: {},
            chartistTotalsOptions: [],
            chartistOriginsData: {labels: [], series: []},
            chartistOriginsChart: {},
            chartistOriginsOptions: [],
            responsiveOptions: [],
            errors: []
        }
    },
    computed: {

    },
    mounted() {
        this.loadingPortfolio = true;

        // Variables initizalization
        this.totalBtc = 0;
        this.totalFiat = 0;
        this.uniqueAssetsOriginFiat = [];
        this.uniqueAssetsOriginName = [];
        this.hideSmallAssets = this.hidesmall;
        this.minAssetValue = this.minvalue; 
        
        // Setup DATATABLE
        this.portfolioTable = $('#portfolioTable').DataTable( {
            "columns": [
                { name: 'coin', title: '<div class="sorting nowrap">Coin</div>'},
                { name: 'profit', title: '<div class="sorting nowrap">Profit</div>'},
                { name: 'fiatvalue', title: '<div class="sorting nowrap">Value (Fiat)</div>' },
                { name: 'amount', title: '<div class="sorting nowrap">Amount</div>' },
                { name: 'btcvalue', title: '<div class="sorting nowrap">Value (BTC)</div>' },
                { name: 'last', title: '<div class="sorting nowrap">Last Price</div>' },
                { name: 'purchase', title: '<div class="sorting nowrap">Purchase Price</div>' },
                { name: 'id', title: '<div class="sorting nowrap">Asset ID</div>' },
                { name: 'origin', title: '<div class="sorting_asc nowrap">Origin</div>' }
            ],
            "searching": false,
            "responsive": true,
            "colReorder":true,
            "stateSave": true,
            "paging": false,
            "info": false,
            "columnDefs": [
                { "visible": false, "targets": 7 }
            ],
            "drawCallback": function ( settings ) {
                var api = this.api();
                var rows = api.rows( {page:'current'} ).nodes();
                var last=null;

                api.column('origin:name', {page:'current'} ).data().each( function ( group, i ) {
                    if ( last !== group ) {
                        $(rows).eq( i ).before(
                            '<tr class="group"><td colspan="' + api.column( 'origin:name' ).index() +'">' + group + '</td></tr>'
                            );

                        last = group;
                    }
                } );
            }
            
        } );
        
        // Clear Datatable in case of reloading
        this.portfolioTable.clear();
        this.portfolioTable.order.fixed( {
            pre: [ this.portfolioTable.column( 'origin:name' ).index(), 'asc' ]
        } );

        // Set Chart options
        this.responsiveOptions = [
          ['screen and (min-width: 200px)', {
            horizontalBars: true,
            seriesBarDistance: 5
          }],
          // Options override for media > 800px
          ['screen and (min-width: 800px)', {
            stackBars: false,
            seriesBarDistance: 10
          }],
          // Options override for media > 1000px
          ['screen and (min-width: 1000px)', {
            horizontalBars: false,
            seriesBarDistance: 15
          }]
        ];
        this.chartistTotalsOptions = {
            distributeSeries: true,
        };
        this.chartistOriginsOptions = {
            distributeSeries: true,
        };

        // Call the LoadPortfolio event asyncronously
        let uri = '/api/portfolio/refresh';
        axios(uri, {
            method: 'GET',
        })
        .then(response => {
            console.log("Reloading portfolio...");
        })
        .catch(e => {
            this.errors.push(e);
           
            console.log("Error: " + e.message);
        });

        // Listen to ASSETS events
        Echo.private('assets.' + this.portfolio.id)
        .listen('PortfolioAssetLoaded', (e) => {
            // ************
            // ASSET LOADED
            // ************
    
            if (this.portfolio.update_id == e.asset.update_id) {

                if ( (this.hideSmallAssets == "on" && parseFloat(e.asset.balance) > parseFloat(this.minAssetValue)) || this.hideSmallAssets != "on" ) {
                    var tools = '<div class="asset-tools nowrap"><button class="clear button" data-open="edit-asset-modal"><i class="fa fa-pencil edit-icon" aria-hidden="true"></i></button></div>';

                    // Set coin url, logo, name and symbol
                    var coin = '<div class="asset-info nowrap"><a href="' + e.asset.info_url + '" target="_blank"><img class="asset-img" src="' + e.asset.logo_url + '" width="20"></a> <span class="show-for-medium asset-name">' + e.asset.full_name + '</span> <span class="asset-symbol">' + e.asset.symbol + '</span></div>';

                    // Set coin amount
                    var amount = '<div class="asset-amount nowrap">' + parseFloat(e.asset.amount).toFixed(4) + '</div>'

                    // Set coin origin
                    var origin = '<div class="asset-origin  nowrap">' + e.asset.origin_name + '</div>';

                    var counterValue = '<div class="asset-counter-value nowrap">' + parseFloat(e.asset.counter_value).toFixed(2) + '</div>';
                    
                    // Add row to the table with the new asset
                    this.portfolioTable.row.add( [
                        coin,
                        '',  
                        counterValue,
                        amount,
                        parseFloat(e.asset.balance).toFixed(8),
                        parseFloat(e.asset.price).toFixed(8),
                        parseFloat(e.asset.initial_price).toFixed(8),
                        e.asset.id,
                        origin
                    ] ).order( [ this.portfolioTable.column( 'origin:name' ).index(), 'asc' ] ).invalidate().draw();   
                }
            }
            
        })
        .listen('PortfolioAssetUpdated', (e) => {
            // ************
            // ASSET UPDATED
            // ************
            // 
            if ( this.portfolio.update_id == e.asset.update_id ) {
                
                // Calculate current TOTAL balances (btc and fiat)
                this.totalBtc = (parseFloat(this.totalBtc) + parseFloat(e.asset.balance)).toFixed(8);
                this.totalFiat = (parseFloat(this.totalFiat) + parseFloat(e.asset.counter_value)).toFixed(2);

                // Store TOTAL balances
                var balance = parseFloat(e.asset.balance).toFixed(8);
                var price = parseFloat(e.asset.price).toFixed(8);
                var counter_value = parseFloat(e.asset.counter_value).toFixed(2);
                var purchase_price = parseFloat(e.asset.initial_price).toFixed(8);

                // Store a consolidated array by VALUE
                var indexRepeatedAsset = this.uniqueAssetsName.indexOf(e.asset.symbol);

                if ( indexRepeatedAsset >= 0 ) {
                    // If asset is already counted we sum the new value
                    var newBalanceBtc = (parseFloat(this.uniqueAssetsBtc[indexRepeatedAsset]) + parseFloat(e.asset.balance));
                    var newBalanceFiat = (parseFloat(this.uniqueAssetsFiat[indexRepeatedAsset]) + parseFloat(e.asset.counter_value));
                    this.uniqueAssetsBtc[indexRepeatedAsset] = parseFloat(newBalanceBtc);
                    this.uniqueAssetsFiat[indexRepeatedAsset] = parseFloat(newBalanceFiat);
        
                    // Update Chart data
                    this.chartistTotalsData.series[indexRepeatedAsset]= parseFloat( parseFloat( this.uniqueAssetsFiat[indexRepeatedAsset]).toFixed(2) );
                  
                }
                else {

                    this.coinsCounter ++;

                    // If the asset doesn't exists we push it
                    this.uniqueAssetsBtc.push(parseFloat(e.asset.balance));
                    this.uniqueAssetsFiat.push(parseFloat(e.asset.counter_value));
                    this.uniqueAssetsName.push(e.asset.symbol);

                    // Update Chart data
                    this.chartistTotalsData.labels.push(e.asset.symbol);
                    this.chartistTotalsData.series.push( parseFloat( parseFloat(e.asset.counter_value).toFixed(2) ));
                }

                // Store consolidated array of ORIGINS
                var indexRepeatedOrigin = this.uniqueAssetsOriginName.indexOf(e.asset.origin_name);
                
                if ( indexRepeatedOrigin >= 0 ) {
                    // If origin is already counted we sum the new value
                    var newBalanceFiat = (parseFloat(this.uniqueAssetsOriginFiat[indexRepeatedOrigin]) + parseFloat(e.asset.counter_value));
                    this.uniqueAssetsOriginFiat[indexRepeatedOrigin] = parseFloat(newBalanceFiat);

                    // Update Chart data
                    this.chartistOriginsData.series[indexRepeatedOrigin]= parseFloat( parseFloat(newBalanceFiat).toFixed(2) );

                }
                else {
                   
                    // If the asset doesn't exists we push it
                    this.uniqueAssetsOriginName.push(e.asset.origin_name);
                    this.uniqueAssetsOriginFiat.push(e.asset.counter_value);
                   
                    // Update Chart data
                    this.chartistOriginsData.labels.push(e.asset.origin_name);
                    this.chartistOriginsData.series.push( parseFloat( parseFloat(e.asset.counter_value).toFixed(2) ));
                }

                if ( (this.hideSmallAssets == "on" && parseFloat(e.asset.balance) > parseFloat(this.minAssetValue)) || this.hideSmallAssets != "on" ) {
                    // Locate current coin row in DATATABLE
                    var indexes = this.portfolioTable.rows().eq( 0 ).filter( rowIdx => {
                        return this.portfolioTable.cell( rowIdx, this.portfolioTable.column( 'id:name' ).index() ).data() === e.asset.id ? true : false;
                    } );

                    // Update DATATABLE values (Price, Balance and Counter Value)
                    var formated_counter_value = '<span class="asset-counter-value nowrap">' + this.counterValueSymbolHtml + parseFloat(counter_value).toFixed(2)  + '</span>';
                    var formated_balance = '<span class="nowrap"><i class="fa fa-btc" aria-hidden="true"></i>' + parseFloat(balance).toFixed(8) + '</span>';
                    var formated_price = '<span class="nowrap"><i class="fa fa-btc" aria-hidden="true"></i>' + parseFloat(price).toFixed(8) + '</span>';

                    var formated_purchase_price = '<span class="nowrap"><i class="fa fa-btc" aria-hidden="true"></i>' + parseFloat(purchase_price).toFixed(8) + '</span>';

                    this.portfolioTable.cell(indexes[0], this.portfolioTable.column( 'fiatvalue:name' ).index()).data(formated_counter_value).invalidate();
                    this.portfolioTable.cell(indexes[0], this.portfolioTable.column( 'btcvalue:name' ).index()).data(formated_balance).invalidate();
                    this.portfolioTable.cell(indexes[0], this.portfolioTable.column( 'last:name' ).index()).data(formated_price).invalidate();
                    this.portfolioTable.cell(indexes[0], this.portfolioTable.column( 'purchase:name' ).index()).data(formated_purchase_price).invalidate();
                    
                    var balance = '<i class="fa fa-btc" aria-hidden="true"></i>' + parseFloat(e.asset.balance).toFixed(8);
                    if (e.asset.initial_price == 0 ) {
                        var profit = "-";
                    }
                    else {
                        var profit = ( ( ( parseFloat(price)-parseFloat(e.asset.initial_price) ) / parseFloat(e.asset.initial_price)) * 100 ).toFixed(2);
                    }
                    if (profit > 0) {
                        this.portfolioTable.cell(indexes[0], this.portfolioTable.column( 'profit:name' ).index()).data('<span class="profit-box nowrap">+' + profit + '%</span>' ).invalidate();
                    }
                    else if (profit < 0){
                        this.portfolioTable.cell(indexes[0], this.portfolioTable.column( 'profit:name' ).index()).data('<span class="loss-box nowrap">' + profit + '%</span>' ).invalidate();
                    }
                    else {
                        this.portfolioTable.cell(indexes[0], this.portfolioTable.column( 'profit:name' ).index()).data('<span class="neutral-box nowrap">' + profit + '%</span>' ).invalidate();
                    }
                
                }

                // Gainers and losers
                if (parseFloat(profit) > parseFloat(this.biggestGainer.profit) && (e.asset.symbol!='BTC' && e.asset.symbol!='BTG')) {
                    this.biggestGainer.profit = profit;
                    this.biggestGainer.symbol = e.asset.symbol;
                }    
                if (parseFloat(profit) < parseFloat(this.biggestLoser.profit) && (e.asset.symbol!='BTC' && e.asset.symbol!='BTG')) {
                    this.biggestLoser.profit = profit;
                    this.biggestLoser.symbol = e.asset.symbol;
                }  

                // this.portfolioTable.responsive.rebuild();
                // this.portfolioTable.responsive.recalc();
                this.portfolioTable.draw();

                // Keep count of number of assets
                this.portfolioCurrentAssetCount++;

                // Keep asset on array of assets
                this.assets.push(e.asset);

                // console.log("Asset Count: " + this.portfolioCurrentAssetCount);
                if (this.portfolioCurrentAssetCount == this.portfolioAssetCount && this.portfolioCurrentAssetCount!=0) {
                    console.log(this.chartistOriginsData.series);
                    console.log(this.chartistOriginsData.labels);
                    this.showChart = true;
                    this.chartistTotalsChart = new Chartist.Bar('.ct-chart-totals', this.chartistTotalsData, this.chartistTotalsOptions,this.responsiveOptions);
                    this.chartistOriginsChart = new Chartist.Bar('.ct-chart-origins', this.chartistOriginsData, this.chartistOriginsOptions,this.responsiveOptions);
                    this.assetsCounter = this.portfolioCurrentAssetCount;

                    $(window).trigger('resize');
                    this.loadingPortfolio = false;

                }
                
            }
        });
        Echo.private('portfolios.' + this.portfolio.id)
        .listen('PortfolioLoaded', (e) => {
            // ************
            // PORTFOLIO LOADED
            // ************
            
            // console.log("Asset count: " + e.assetCount);
            this.counterValueSymbol = this.portfolio.counter_value.toUpperCase();
            this.counterValueSymbolHtml = '<i class="fa fa-' + this.portfolio.counter_value.toLowerCase() + ' aria-hidden="true"></i>';
            this.portfolioAssetCount = e.assetCount;
        });

        console.log('Component TradeList mounted.');
    },
    methods: {
        refreshPortfolio() {
            this.loadingPortfolio = true;

            if (this.portfolioCurrentAssetCount > 0) {
                this.chartistTotalsChart.detach();
                this.chartistOriginsChart.detach();
                this.portfolioTable.clear().draw();
            };
            this.assets = [];
            this.totalBtc = 0;
            this.totalFiat = 0;
            this.biggestLoser = { symbol:"", profit: 0 },
            this.biggestGainer = { symbol:"", profit: 0 },
            this.assetsCounter = 0,
            this.coinsCounter = 0,
            this.uniqueAssetsBtc = [];
            this.uniqueAssetsFiat = [];
            this.uniqueAssetsName = [];
            this.uniqueAssetsOriginFiat = [];
            this.uniqueAssetsOriginName = [];
            this.chartistTotalsData.labels = [];
            this.chartistOriginsData.labels = [];
            this.chartistTotalsData.series = [];
            this.chartistOriginsData.series = [];
            this.portfolioCurrentAssetCount = 0;
            this.portfolioAssetCount = 0;
            this.showChart = false;

            let uri = '/api/portfolio/refresh';
            axios(uri, {
                method: 'GET',
            })
            .then(response => {
                console.log("Reloading portfolio...");
            })
            .catch(e => {
                this.errors.push(e);
                this.loadingPortfolio = false;
                console.log("Error: " + e.message);
            });
        } 
    }
}
</script>


