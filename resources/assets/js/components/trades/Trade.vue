<template>
        <tr>
            <td></td>
            <td v-if="history==false">
                <div class="trade-cancel icons-area">
                    <!-- <i v-show="updating" class="fa fa-cog fa-spin fa-fw loading-icon"></i>  -->
                    <button class="clear button" v-if="tradeStatus=='Opened'" :data-open="'closeTrade' + id"><i class="fa fa-times cancel-icon"></i></button>
                    <button class="clear button"  v-if="tradeStatus=='Waiting'" :data-open="'closeWaitingTrade' + id"><i class="fa fa-times cancel-icon"></i></button>
                    <button class="clear button"  v-if="tradeStatus=='Opening'" :data-open="'cancelOpeningTrade' + id"><i class="fa fa-times cancel-icon"></i></button>
                    <button class="clear button"  v-if="tradeStatus=='Opened'" :data-open="'keepTrade' + id"><i class="fa fa-chevron-circle-down" aria-hidden="true"></i></button>
                    <button class="clear button"  v-if="tradeStatus=='Opened'" :data-open="'editTrade' + id"><i class="fa fa-pencil edit-icon" aria-hidden="true"></i></button>
                    <button class="clear button" v-if="((tradeStatus=='Opened' || tradeStatus=='Waiting' || tradeStatus=='Closing' || tradeStatus=='Opening') && updating==false)" v-on:click="update(exchange, pair, price)"> <i class="fa fa-refresh refresh-icon"></i> </button>
                    <button class="clear button" v-if="((tradeStatus=='Opened' || tradeStatus=='Waiting' || tradeStatus=='Closing' || tradeStatus=='Opening') && updating==true)"> <i class="fa fa-refresh fa-spin refresh-icon"></i></button> 
                </div>
            </td>
            <td v-if="tradeStatus == 'Opened' || tradeStatus == 'Closed'" >{{ (history==true) ? parseFloat(finalProfit).toFixed(2) + '%' : profit }}</td>
            <td v-else class="text-center"> -- </td>
            <td>{{ pair }}</td>
            <td :class="'status-' + tradeStatus" v-model="tradeStatus">{{ tradeStatus }}</td>
            <td>{{ exchange }}</td>
            <td>{{ position }}</td>
            <td><span class="nowarp">{{ last.toFixed(8) }}{{ baseCurrency }}</span></td>
            <td><span class="nowarp">{{ parseFloat(price).toFixed(8) }}{{ baseCurrency }}</span></td>
            <td><span class="nowarp">{{ parseFloat(closingPrice).toFixed(8) }}{{ baseCurrency }}</span></td>
            <td>{{ parseFloat(amount).toFixed(4) }}</td>
            <td><span class="nowarp">{{ parseFloat(total).toFixed(8) }}{{ baseCurrency }}</span></td>
            <td><span class="nowarp">{{ parseFloat(stopLoss).toFixed(8) }}{{ baseCurrency }}</span></td>
            <td><span class="nowarp">{{ parseFloat(takeProfit).toFixed(8) }}{{ baseCurrency }}</span></td>
            <td>{{ (condition == 'now') ? 'none' : condition + ' than' }} </td>
            <td><span class="nowarp">{{ parseFloat(conditionPrice).toFixed(8) }}{{ baseCurrency }}</span></td>
            <td> {{ date }}</td>
        </tr>
        
</template>

   <script>
   export default {
    name: 'trade',
    data: () => {
        return {
            updating: false,
            profit: 0,
            last: 0,
            tradeStatus: "",
            baseCurrency: ""
        }
    },
    props: [
    'status',
    'exchange',
    'position', 
    'pair', 
    'price', 
    'amount', 
    'total', 
    'stop-loss',
    'take-profit',
    'condition',
    'condition-price',
    "final-profit",
    "type",
    "closing-price",
    "timestamp",
    "id"
    ],
    computed: {
        history: function() {
            if (this.type == "history") {
                return true;
            }
            else {
                return false;
            }
        },
        date: function() {
        
            var dateObj = new Date();
            dateObj.setDate(dateObj.getDate());
            var YYYY = dateObj.getFullYear() + '';
            var MM = (dateObj.getMonth() + 1) + '';
            MM = (MM.length === 1) ? '0' + MM : MM;
            var DD = dateObj.getDate() + '';
            DD = (DD.length === 1) ? '0' + DD : DD;
            return DD + "/" + MM + "/" + YYYY;
        
            // let fullDate = new Date(this.timestamp);
            // return fullDate.getDate() + "/" + (fullDate.getMonth()+1) + "/" + fullDate.getFullYear();
     
        }
    },
    mounted() {
        this.tradeStatus = this.status;

        this.baseCurrency = this.pair.split('/')[1];

        this.update(this.exchange, this.pair, this.price); 

        Echo.private('trades.' + this.id)
        .listen('TradeOpened', (e) => {
            console.log('New status: ' + e.trade.status);
            this.tradeStatus = e.trade.status;
        })
        .listen('TradeClosed', (e) => {
            console.log('New status: ' + e.trade.status);
            this.tradeStatus = e.trade.status;
        })
        .listen('TradeCancelled', (e) => {
            console.log('New status: ' + e.trade.status);
            this.tradeStatus = e.trade.status;
        });

        console.log('Component Trade mounted.');
    },
    methods: {
        update(exchange, pair, price) {
            let percent = 0;
            this.updating = true;

            let params = "?coin=" + pair.split("/")[0] + "&base=" + pair.split("/")[1];
            let uri = '/api/broker/getticker/' + exchange + '/' + pair.split("/")[0] + "/" + pair.split("/")[1];
            axios.get(uri)
            .then(response => {

                this.last = parseFloat(response.data.result.ticker.last);
                
                // Calculate percentual diference
                let decreaseValue = this.last - price;
                decreaseValue = (decreaseValue / price) * 100;
                this.profit = decreaseValue.toFixed(2) + "%";

                this.updating = false;
                
            })
            .catch(e => {

                this.updating = false;
                console.log("Error: " +  e.message);

            })
            
        }

    }
}
</script>


