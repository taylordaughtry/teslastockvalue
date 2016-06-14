Wee.fn.make('common', {
	init: function() {
		var scope = this.$private;

		scope.initializeStorage();
		scope.initializeApp();
		scope.addHelpers();
		scope.bind();
		scope.monitorStock();
	}
}, {
	bind: function() {
		var scope = this;

		$.events.on({
			'ref:buy': {
				'click': function(e) {
					var data = Wee.app.stockManager.$get();

					if (data.trader.capital > data.price) {
						scope.modifyStock('buy', e.shiftKey, e.altKey);
					}
				}
			},

			'ref:sell': {
				'click': function(e) {
					if (Wee.app.stockManager.$get('trader.stock')) {
						scope.modifyStock('sell', e.shiftKey, e.altKey);
					}
				}
			}
		});
	},

	initializeApp: function() {
		Wee.data.request({
			url: 'https://www.google.com/finance/info?q=TSLA',
			jsonp: true,
			success: function(response) {
				var data = {};

				data.symbol = response[0].t;
				data.price = response[0].l_cur; // jscs:ignore
				data.absoluteChange = response[0].c_fix; // jscs:ignore
				data.percentChange = response[0].cp_fix; // jscs:ignore
				data.trader = JSON.parse(localStorage.getItem('trader'));

				Wee.app.make('stockManager', {
					target: 'ref:stock',
					view: 'stock',
					model: data
				});
			}
		});
	},

	initializeStorage: function() {
		if (Wee._win.localStorage && localStorage.getItem('trader')) {
			return;
		}

		var trader = {
			name: 'John Doe',
			capital: 100000,
			earnings: 0,
			timeLeft: Math.floor(new Date().getTime() / 1000),
			stockAmount: 0,
			stock: 0
		};

		localStorage.setItem('trader', JSON.stringify(trader));
	},

	modifyStock: function(type, shifted, ctrled) {
		var data = Wee.app.stockManager.$get(),
			amount = ctrled ? 100 : shifted ? 10 : 1,
			stockPrice = data.price * amount,
			capital;

		if (type == 'buy') {
			capital = data.trader.capital - parseInt(stockPrice);

			Wee.app.stockManager.$set('trader.capital', capital);
			Wee.app.stockManager.$set('trader.earnings', capital - 100000);
			Wee.app.stockManager.$set('trader.stock', data.trader.stock + amount);
		} else {
			capital = data.trader.capital + parseInt(stockPrice);

			Wee.app.stockManager.$set('trader.capital', capital);
			Wee.app.stockManager.$set('trader.stock', Math.abs(data.trader.stock - amount));
			Wee.app.stockManager.$set('trader.earnings', capital - 100000);
		}

		localStorage.setItem('trader', JSON.stringify(data.trader));
	},

	monitorStock: function() {
		setInterval(function() {
			Wee.data.request({
				url: 'https://www.google.com/finance/info?q=TSLA',
				jsonp: true,
				success: function(response) {
					Wee.app.stockManager.$set('symbol', response[0].t);
					Wee.app.stockManager.$set('price', response[0].l_cur); // jscs:ignore
					Wee.app.stockManager.$set('absoluteChange', response[0].c_fix); // jscs:ignore
					Wee.app.stockManager.$set('percentChange', response[0].cp_fix); // jscs:ignore
					Wee.app.stockManager.$set('trader', JSON.parse(localStorage.getItem('trader')));
				}
			});
		}, 5000);
	},

	addHelpers: function() {
		Wee.view.addHelper('formatMoney', function() {
			return parseInt(this.val).toFixed(2).replace(/(\d)(?=(\d{3})+\.)/g, '$1,');
		});

		Wee.view.addHelper('formatTime', function() {
			return Math.floor((480 - ((new Date().getTime() / 1000) - parseInt(this.val)) / 60) / 60);
		});
	}
});