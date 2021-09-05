/**
 * Survey Element
 *
 * @copyright: Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

define(['jquery'], function (jQuery) {
	var FbSurveyList = new Class({
		Implements: [Events, Options],

		initialize: function (id, options) {

			this.setOptions(options);
			var values = [], labels = [], numValues = [], exists, table_name, element_name;

			values = this.options.values;
			labels = this.options.labels;
			exists = this.options.exists;

			if (this.options.canSurvey) {
				this.createEvents (values, labels, exists, 0);
			}
		},
		createEvents: function (values, labels, exists, rowid) {
			for (i = 0; i < values.length; i++) {
				var a = document.getElements ('.' + values[i]);
				a.each ((a) => {
					var value = a.getAttribute ('class');
					var rowId = a.getAttribute ('id');
					if ((rowid === 0) || (rowid === rowId)) {
						a.addEvent ('click', e => {
							e.stop ();
							if (a.getAttribute ('style')) {
								this.doAjax (a, value, values, labels, rowId, 1);
							} else {
								this.doAjax (a, value, values, labels, rowId, 0);
							}
						});
					}
				});
			}
		},
		encode: function (item) {
			var i=0, encodeItem = [];
			for (i=0; i<item.length; i++) {
				encodeItem[i] = encodeURIComponent(item[i]);
			}
			return encodeItem;
		},
		doAjax: function (e, value, values, labels, rowId, exists) {
			var row = e.getParent();

			Fabrik.loader.start(row);
			var data = {
				'option': 'com_fabrik',
				'format': 'raw',
				'task': 'plugin.pluginAjax',
				'plugin': 'survey',
				'method': 'ajax_rate',
				'g': 'element',
				'value': value,
				'values': values,
				'labels' : labels,
				'rowId': rowId,
				'exists' : exists,
				'table_name' : this.options.table_name,
				'element_name' : this.options.element_name,
				'elementname': this.options.elid,
				'userid': this.options.userid,
				'listid': this.options.listid,
				'formid': this.options.formid
			};

			new Request ({
				url: '',
				'data': data,
				onComplete: (r => {
					Fabrik.loader.stop(row);
					r = JSON.parse(r);
					this.updateElements(e, values, labels, r, value, exists);
				}).bind (this)
			}).send ();
		},
		updateElements: function (e, values, labels, r, value, exists) {
			var div = e.getParent ();
			var rowId = e.getAttribute ("id");

			if (div) {
				var child = div.firstChild;
				while (child) {
					div.removeChild (child);
					child = div.firstChild;
				}
			}

			var n = document.createElement ("a");
			var o = document.createElement ("span");
			var i = 0;
			while (values[i]) {
				n.setAttribute ("href", "#void");
				n.setAttribute ("class", values[i]);
				n.setAttribute ("id", rowId);
				n.setAttribute ("name", value + rowId);

				n.innerHTML = labels[i] + " (";

				o.setAttribute ("class", "valueItem");
				o.innerHTML = r[i];

				n.appendChild (o);
				o.insertAdjacentHTML("afterend", ")");
				if (div) {
					div.appendChild (n);
					div.innerHTML += "<br>";
				}

				i ++;
			}

			if (exists !== 1) {
				var p = div.getElementsByClassName (value)[0];
				p.setAttribute ("style", "text-decoration: underline;");
			}

			this.createEvents (values, labels, exists, rowId);
		}

	});

	return FbSurveyList;
});