	define([ // libraries we use
		"jquery",
		"jquery/ui",
		"Magento_Ui/js/modal/confirm"
	], function (jQuery, ui, confirmation) {

		"use strict"; //  With strict mode, you can not, for example, use undeclared variables

		jQuery.widget('wolf.categoryfilter', {

			version: '1.0',

			selects: [],

			cars: [],
			
			keepGarageOpen: false,
			
			removeCarButtons: [],

			addNewCarButton: null,

			changeCarButton: null,

			loadingMessage: '',

			testMethodA: function () {
				//alert('tetMethodA');
			},

			_testMethodB: function () {
				//alert('tetMethodB');
			},

			_create: function () {

				this.element.addClass('categoryfilter');

				this.selects = jQuery('#cd-' + this.options.nameInLayout).find('.category-filter-select');
				this.selects.on('change', this, this._onChange);


				this.changeCarButton = jQuery('.change-car-btn');
				this.changeCarButton.on('click', this, this._onClickChangeCar)


				this._loadGarage();

			},

			_onChange: function (event) {
				var select = this;
				var selectedValue = select.value;

				var redirectUrl = jQuery('option:selected', this).attr('dataUrl');
				var self = event.data;
				var dataId = jQuery(this).attr('dataId');
				var levels = self.options.levels;
				var labelembedded = self.options.labelembedded;
				var nameInLayout = self.options.nameInLayout;
				var nextDropdown = parseInt(dataId) + 1;
				self._clearDropDowns(levels, dataId);
				self._loadNextDropDown(event, selectedValue, labelembedded, nameInLayout, redirectUrl, dataId, levels);
			},

			_loadNextDropDown: function (event, selectedValue, labelembedded, nameInLayout, redirectUrl, dataId, levels) {

				var self = event.data;
				var url = self.options.url;
				var redirectUrl = redirectUrl;
				var category_id = self.options.category_id;
				var nextDropdown = parseInt(dataId) + 1;
				var selectedValue = selectedValue;

				// console.log('_loadNextDropDown');
				// console.log('dataId', dataId);
				// console.log('levels', levels);
				// console.log('selectedValue', selectedValue);

				if (dataId == levels) { // Last Select

					if (selectedValue != "") {

						jQuery('.add-new-car-btn').css('display', 'none');
						jQuery('.loading-message').html('Loading...');
						jQuery('.loading-message').css('display', 'inline-block');
						jQuery.cookie('car_selected', 'selected', { path: '/', domain: window.location.hostname });

						/**
						 * 2019-07-26 Dmitry Fedyuk https://www.upwork.com/fl/mage2pro
						 * 1) "Fix Magento 2 Extension Full Page Cache": https://www.upwork.com/ab/f/contracts/22580506
						 * 2) The previous code:
						 * 		window.location.href = redirectUrl;
						 */
						var form = document.createElement('form');
						form.setAttribute('method', 'post');
						form.setAttribute('action', redirectUrl);
						document.body.appendChild(form);
						var hiddenField = document.createElement('input');
						hiddenField.setAttribute('type', 'hidden');
						hiddenField.setAttribute('name', 'form_key');
						hiddenField.setAttribute('value', jQuery.cookie('form_key'));
						form.appendChild(hiddenField);						
						form.submit();

					}

				} else {

					if (selectedValue != "") {
						var labelText =
							'embedded' !== labelembedded ? 'Please Select' :
								jQuery("#" + nameInLayout + nextDropdown + " option:first").text()
						;
						jQuery('#' + nameInLayout + nextDropdown).empty();
						jQuery('#' + nameInLayout + nextDropdown).append('<option value="">Loading...</option>');
						jQuery('#' + nameInLayout + nextDropdown).attr('disabled', true);

						var requestData = {
							selectedValue: selectedValue,
							dataId: dataId,
							category_id: category_id,
							levels: levels }

						for(var s = 0; s < this.selects.length; s++) {
							requestData['level_' + s + '_value'] = this.selects[s].value;
						}

						jQuery.ajax({
							url: url,
							type: 'get',
							data: requestData,
							dataType: 'json',
							showLoader: false,
							success: function (data) {

								if (data.length) {

									var optionStr = "";
									var first = true;
									var counter = 0;
									for (var j in data) {
										
										counter = counter + 1;
									}
									
									for (var i in data) {
										if (first) {
											first = false;
											optionStr = "<option value=''>" + labelText + "</option>";
										}
										if (data[i]['id'] == "NA") {
											optionStr = optionStr + '<option value="' + data[i]['id'] + '" dataUrl="' + data[i]['url'] + '" selected>' + data[i]['name'] + '</option>';
										} else {
											
												var isSelected = "";
												if(counter == 1){
													
													isSelected = "selected";
												}
											
											optionStr = optionStr + '<option value="' + data[i]['id'] + '" dataUrl="' + data[i]['url'] + '"'+ isSelected+ '>' + data[i]['name'] + '</option>';
										}
									}
									jQuery('#' + nameInLayout + nextDropdown).empty();
									jQuery('#' + nameInLayout + nextDropdown).append(optionStr);
									jQuery('#' + nameInLayout + nextDropdown).attr('disabled', false);
									if (dataId != levels) {

										var e = document.getElementById(nameInLayout + nextDropdown);
										var na_selected_value = e.options[e.selectedIndex].value;

										if (na_selected_value == "NA") {

											dataId = parseInt(dataId) + 1;

											self._loadNextDropDown(event, e.value, finderId, dataId, levels, url);
										}else{
											
											var eventName = 'change';
											var event;
											if (typeof(Event) === 'function') {
												event = new Event(eventName);
											} else {
												event = document.createEvent('Event');
												event.initEvent(eventName, true, true);
											}
											e.dispatchEvent(event);
										}
									}

								} else {

									jQuery('#' + nameInLayout + nextDropdown).empty();
									jQuery('#' + nameInLayout + nextDropdown).append("<option value=''>Please Select</option>");

								}

							}
						});


					}
				}
			
			},

			_clearDropDowns: function (count, finderId, dataId) {
				for (var j = 1; j < count; j++) {
					if (j >= dataId) {
						document.getElementById(nameInLayout + (j + 1)).selectedIndex = 0;
						jQuery('#' + nameInLayout + (j + 1)).empty();
						jQuery('#' + nameInLayout + (j + 1)).append("<option value=''>Please Select</option>");
					}
				}
			},

			fillCars: function (cars, keepGarageOpen) {



				// console.log('fillCars');
				// console.log('cars');
				// console.log(cars);

				var self = this;

				// var garageSelect = this.garageSelect;



				// garageSelect.empty();
				// garageSelect.attr('disabled', true);

				var oneCarIsSelected = false;
				var listStr = "";

				self.selected = false;
				self.selectedKey = null;
				self.selectedValue = null;
				self.selectedText = null;



				jQuery.each(cars, function(key, value) {


					var text = value.replace(/\/|-/g,' ');

					// remove .html
					text = text.slice(0, -5);

					// ucwords
					text = text.replace(/\b[a-z]/g, function(letter) {
						return letter.toUpperCase();
					});

					// garageSelect
					//     .append(jQuery("<option></option>")
					//         .attr("value",value)
					//         .attr("selected", selected)
					//         .text(text));

					listStr = listStr +
						'<tr class="garage-table-row">' +
						'   <td><a href="' + value + '">' + text + '</a></td>' +
						'   <td class="remove-car-button-cont">' +
						'       <button class="remove-car-button" type="button" data-car-uri="' + value +'" >x</button> <span> &nbsp; &#9632;</span></td>' +
						'</tr>';

					// if value without .html is contained in location.pathname, oneCarIsSelected
					var tmpValue = value.replace('.html', '');

					// if(value == location.pathname) {

					if(location.pathname.indexOf(tmpValue) !== -1) {
						self.selected = true;
						self.selectedKey = key;
						self.selectedValue = value;
						self.selectedText = text;
					}

				});


				if(self.selected) {
					jQuery('.garage-selected-car-cont').css('display', 'inline-block');
					jQuery('.garage-selected-car-link').attr("href", self.selectedValue);
					jQuery('.garage-selected-car-link').html(self.selectedText);
				} else {
					//jQuery('.garage-selected-car-cont').css('display', 'none');
				}

				// console.log('listStr', listStr);




				if(listStr.length) {

					listStr = listStr + '<tr><td colspan="2" style="text-align: right"><a class="remove-all-button" href="#remove-all">Remove All</a></td></tr>'
					listStr = '<table class="garage-table">' +  listStr + '</table>';

					jQuery('#change-car').html('');
					jQuery('#change-car').append(listStr);
					jQuery('#change-car').show();


					// link newly added buttons to its actions
					this.removeAllButton = jQuery('.remove-all-button');
					this.removeAllButton.on('click', this, this._onClickRemoveAll)
					this.removeCarButtons = jQuery('.change-car-form').find('.remove-car-button');
					this.removeCarButtons.on('click', this, this._onClickRemoveCar);
					// this.garageTableRows = jQuery('.garage-table-row');
					// this.garageTableRows.on('mouseenter', this, this._onGarageTableRowMouseenter);
					// this.garageTableRows.on('mouseleave', this, this._onGarageTableRowMouseleave);
					//
					jQuery('.garage-table-row').mouseover(function () {
						jQuery(this).addClass('garage-table-row-over');
						jQuery(this).find('.remove-car-button').show();
						jQuery(this).find('span').css('color', 'silver');
					});
					jQuery('.garage-table-row').mouseout(function () {
						jQuery(this).removeClass('garage-table-row-over');
						jQuery(this).find('.remove-car-button').hide();
						jQuery(this).find('span').css('color', '#E0E0E0');
					});




				} else {

					jQuery('#change-car').html('');
					jQuery('#change-car').hide();

				}

				if(window.location.pathname == '/') { // homepage

					jQuery('.garage-cont').css('display', 'inline-block');
					jQuery('.garage-selected-car-cont').css('display', 'none');

				} else {



					if(self.selected) {
						jQuery('.garage-selected-car-cont').css('display', 'inline-block');
						if(!keepGarageOpen) { jQuery('.garage-cont').css('display', 'none'); }
					} else {
						//jQuery('.garage-selected-car-cont').css('display', 'none');
						if(listStr.length) {
							//jQuery('.garage-cont').css('display', 'inline-block');
						}
					}



				}

				// jQuery('.garage-selected-car-cont').css('display', 'inline-block');

				jQuery('.loading-message').css('display', 'none');
				jQuery('.garage-opening-message').css('display', 'none');


			},

			_onClickChangeCar: function() {

				// console.log('_onClickChangeCar');

				var garageContDisplay = jQuery('.garage-cont').css('display');
				if(garageContDisplay == 'none') {
					jQuery('.garage-cont').css('display', 'inline-block');
				} else {
					jQuery('.garage-cont').css('display', 'none');
				}

			},

			_onClickRemoveAll: function() {

				// console.log('_onClickRemoveAll');

				jQuery('.loading-message').html('Loading...');
				jQuery('.loading-message').css('display', 'inline-block');

				confirmation({
					title: 'Remove all cars',
					content: 'Are you sure?',
					actions: {
						confirm: function(){

							jQuery.ajax({
								url: '/categoryfinder/garage/clean',
								type: 'get',
								dataType: 'json',
								showLoader: false,
								success: function (data) {

									// console.log('/categoryfinder/garage/clean success');
									// console.log('data', data);
									// console.log('location.pathname', location.pathname);
									// console.log('garageSelect', garageSelect);

									if(data['cars']  && data['cars'].length > 0) {

										jQuery('.categoryfilter').categoryfilter('fillCars', data['cars']);
										// jQuery('.garage-selected-car-cont').css('display', 'inline-block');

									} else {

										jQuery('.categoryfilter').categoryfilter('fillCars', []);
										window.location = '/';

									}

								}
							});

						},
						cancel: function(){
							jQuery('.loading-message').hide();
						}
					}
				});

			},

			_onClickRemoveCar: function() {

				// console.log('_onClickRemoveCar');

				var uri = this.dataset.carUri;

				jQuery('.loading-message').html('Loading...');
				jQuery('.loading-message').css('display', 'inline-block');

				confirmation({
					title: 'Remove car',
					content: 'Are you sure?',
					actions: {
						confirm: function(){
							jQuery.ajax({
								url: '/categoryfinder/garage/remove',
								type: 'get',
								dataType: 'json',
								showLoader: false,
								data: {uri: uri},
								success: function (data) {

									console.log('/categoryfinder/garage/remove success');
									console.log('data', data);
									console.log('location.pathname', location.pathname);



									if(!jQuery.isEmptyObject(data.customer_garage.cars)) {

										jQuery('.categoryfilter').categoryfilter('fillCars', data.customer_garage.cars, true);


									} else {

										jQuery('.categoryfilter').categoryfilter('fillCars', []);

									}

									if(location.pathname == uri) {

										jQuery('.loading-message').html('Loading...');
										jQuery('.loading-message').css('display', 'inline-block');
										window.location = '/';


									}



								}
							});
						},
						cancel: function(){
							jQuery('.loading-message').hide();
						}
					}
				});
			},

			_loadGarage: function () {

				jQuery.ajax({
					url: '/categoryfinder/garage/index',
					type: 'get',
					dataType: 'json',
					showLoader: false,
					success: function (data) {

						// console.log('/categoryfinder/garage/index success');
						// console.log('data', data);

						if(data['cars']  && data['cars'].length > 0) {

							jQuery('.categoryfilter').categoryfilter('fillCars', data['cars']);

						} else {

							jQuery('.categoryfilter').categoryfilter('fillCars', []);

						}

					}
				});
			}

		});
		return jQuery.wolf.categoryfilter;
	});
