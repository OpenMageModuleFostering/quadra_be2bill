var Be2bill = Class.create();
Be2bill.prototype = {
    /**
     * Init Class
     */
    initialize: function () {
   
        this.elements = {
            currency: $('currency'),
            account_type: $('account_type'),
            mode: $('mode'),
            allowed_countries: $('allowspecific_countries_code'),
            logo_url: $('logo_url'),
            logo_image: $('logo_image'),
            name: $('name'),
            allowspecific: $('allowspecific'),
            secure_allowspecific: $('secure_allowspecific'),
            secure_active: $('secure_active'),
            secure: $('3dsecure'),
            delivery: $('delivery'),
        };

        this.urls = {
            currency: $('urlLoadAccountsType').value,
            account_type: $('urlLoadModes').value,
            options: $('urlLoadOptions').value,
            countries: $('urlLoadCountries').value,
            logo: $('urlLoadLogo').value
        };

        this.steps = {
            currency: 'account_type',
            account_type: 'mode'
        };

        this.options = [
            $('standard'),
            $('defered'),
            $('delivery'),
            $('ntimes'),
            $('3dsecure'),
            $('oneclick'),
            $('recurring'),
            $('ageverification')
        ];

        //change le texte de la suppresion du logo
        if($$('label[for="logo_delete"]')[0])
        	$$('label[for="logo_delete"]')[0].innerHTML = $('del_img').value;
        
     	
        this.opts = $('options').value;
        this.accountId = $('id').value;

        this.updateSelect = this.updateSelect.bindAsEventListener(this);
        this.ajaxFailure = this.ajaxFailure.bindAsEventListener(this);
        this.displayOptions = this.displayOptions.bindAsEventListener(this);
        this.displayCountries = this.displayCountries.bindAsEventListener(this);
        this.displayLogo = this.displayLogo.bindAsEventListener(this);

        //Ajout des observeurs
        Event.observe(this.elements.currency, 'change', this.loadOptionsSelect.bind(this));
        Event.observe(this.elements.account_type, 'change', this.loadOptionsSelect.bind(this));
        Event.observe(this.elements.allowspecific, 'change', this.allowSpecificCountry.bind(this));
        Event.observe(this.elements.secure_allowspecific, 'change', this.allowSpecificCountry.bind(this));
        Event.observe(this.elements.secure_active, 'change', this.showHideOptions.bind(this));
        
        if($('mirakl_status'))
        	Event.observe($('mirakl_status'), 'change', this.showHideSlaveConf.bind(this));
        
        if($('delivery_active'))
        	Event.observe($('delivery_active'), 'change', this.showHideSlaveConf.bind(this));

        //Désactive et cache les groupes d'options
        this._disabledAllOptions();

        //cache tout les options du select avec les pays
        this.elements.allowed_countries.select('option').invoke('hide');
        
        if (this.accountId == 0) {
            //Désactive les deux select vide
            this._disableEnableAll(this.elements.account_type, true);
            this._disableEnableAll(this.elements.mode, true);
        } else {
        	
            //affichage des groupes d'options
            this.opts.split(',').each(function (element) {
                if ($(element)) {
                    this._hideShowGroup($(element), false);
                }
            }.bind(this));

           
            this.loadCountries(this.elements.account_type.value);

            // 3D secure
            if (this.elements.secure_active.value == 0 || this.elements.secure_active.value == 2) {
                this.elements.secure.select('table tr:nth(3),table tr:nth(4),table tr:nth(5),table tr:nth(6)').invoke('hide');
            } else {
                this.elements.secure.select('table tr:nth(3),table tr:nth(4),table tr:nth(5),table tr:nth(6)').invoke('show');
            }

			this.showHideSlaveConf();
        }
   },
    disabledForm: function (event) {
        var elmt = $(Event.element(event));
        var div = elmt.up('div.fieldset');
        if (elmt.value == '0') {
            this._disableEnableAll(div, true, elmt.id);
        } else {
            this._disableEnableAll(div, false, elmt.id);
        }

    },
    allowSpecificCountry: function (event) {
        var elmt = $(Event.element(event));
        if (elmt.value == 0) { //Tous les pays autorisés
            $(elmt.id + '_countries_code').disabled = true;
        } else { //Certains pays
            $(elmt.id + '_countries_code').disabled = false;
        }
   },
    /**
     * 3D secure choix full ou selective
     */
    showHideOptions: function (event) {
        var elmt = $(Event.element(event));
        if (elmt.value == 0 || elmt.value == 2) {
            $('3dsecure').select('table tr:nth(3),table tr:nth(4),table tr:nth(5),table tr:nth(6)').invoke('hide');
        } else {
            $('3dsecure').select('table tr:nth(3),table tr:nth(4),table tr:nth(5),table tr:nth(6)').invoke('show');
        }
    },
    /**
     * Afficher / Masquer les id slave pour Mirakl
     */
    showHideSlaveConf: function (event) {
    	// Options Mirakl
        var bIsMPAvailable = false;
        if($('mkp_code_list')){
        	var aMkpCodeList = $('mkp_code_list').value.split(',');
    		for (var i = 0, len = aMkpCodeList.length; i < len; i++) {
    			if(aMkpCodeList[i] == this.elements.account_type.value){
    				bIsMPAvailable = true;
    			}
    		}
        }
        

		// si Mirakl dispo pour ce MP
        if (bIsMPAvailable){
			// si operateur uniquement, je desactive les champs mirakl
	        if ($('delivery_active').value ==0 || $('mirakl_status').value == 'op_only') {
	            $('delivery').select('table tr:nth(5),table tr:nth(6)').invoke('hide');
	            $('mirakl_status').removeClassName('required-entry');
	            
	            $('delivery').select('table tr:nth(4)').invoke('show');
	            $('mkp_login').removeClassName('required-entry');
	            $('mkp_password').removeClassName('required-entry');
	        }
	        // sinon actif et obligatoire
	        else {
	            $('delivery').select('table tr:nth(4),table tr:nth(5),table tr:nth(6)').invoke('show');
	            $('mirakl_status').addClassName('required-entry');
	            $('mkp_login').addClassName('required-entry');
	            $('mkp_password').addClassName('required-entry');
	        }
        }
        else {
            $('delivery').select('table tr:nth(4),table tr:nth(5),table tr:nth(6)').invoke('hide');
            if($('mirakl_status'))
            	$('mirakl_status').removeClassName('required-entry');
            if($('mkp_login'))
            	$('mkp_login').removeClassName('required-entry');
            if($('mkp_password'))
            	$('mkp_password').removeClassName('required-entry');
        }
    },
    
    /**
     * Ajax : Maj des options des selects
     */
    loadOptionsSelect: function (event, id) {
        if (id != null) {
            var elmt = this.elements[id];
        } else {
            var elmt = $(Event.element(event));
        }

        //on recupere l'url
        var url = this.urls[elmt.id];

        if (url != null) {
            //Ajax
            var request = new Ajax.Request(url, {
                method: 'post',
                onSuccess: this.updateSelect.bind(this),
                onFailure: this.ajaxFailure.bind(this),
                parameters: {
                    value: elmt.value,
                    id: this.steps[elmt.id]
                }
            });
        }
        //Chargement des options et de la restriction des pays
        if (elmt.id == this.elements.account_type.id) {
            this.loadOptions(elmt.value);
            this.loadCountries(elmt.value);
            this.loadLogo(elmt.value);

            //Maj du champ label
            this.elements.name.value = this.elements.account_type.options[ this.elements.account_type.selectedIndex ].text;
        }

		this.showHideSlaveConf();

    },
    /**
     * AJAX response : add options
     */
    updateSelect: function (transport) {
        if (transport && transport.responseText) {
            try {
                response = eval('(' + transport.responseText + ')');
            } catch (e) {
                response = {};
            }
        }

        if (response.result != null) {
            //suppression des options du select
            this.elements[response.id].select('option').invoke('remove');
            response.result.each(function (objt) {
                this.elements[response.id].insert(new Element('option', {
                    value: objt.value
                }).update(objt.label));
            }.bind(this));

            //charger et activer le select suivant
            this.loadOptionsSelect(null, response.id);
            this._disableEnableAll(this.elements[response.id], false);
        }

    },
    loadOptions: function (val) {
        var urlOptions = this.urls.options;
        new Ajax.Request(urlOptions, {
            method: 'post',
            onSuccess: this.displayOptions.bind(this),
            onFailure: this.ajaxFailure.bind(this),
            parameters: {
                value: val
            }
        });
    },
    displayOptions: function (transport) {
        if (transport && transport.responseText) {
            try {
                response = eval('(' + transport.responseText + ')');
            } catch (e) {
                response = {};
            }
        }
        //1 : on cache toutes les options
        this._disabledAllOptions();

        //2 : pour afficher uniquement celles dispo
        if (response) {
            response.each(function (objt) {
                var group = $(objt.option);
                if (group) {
                    this._hideShowGroup(group, false);
                }
            }.bind(this));
        }
    },
    loadCountries: function (val) {
        var urlCountries = this.urls.countries;
        new Ajax.Request(urlCountries, {
            method: 'post',
            onSuccess: this.displayCountries.bind(this),
            onFailure: this.ajaxFailure.bind(this),
            parameters: {
                value: val
            }
        });
    },
    displayCountries: function (transport) {
        if (transport && transport.responseText) {
            try {
                response = eval('(' + transport.responseText + ')');
            } catch (e) {
                response = {};
            }
        }

        if (response) {
            //this.elements.allowspecific.select('option')[1].selected = true;

            $$('select#mySelect option');
            if (response.found == 'no-restriction') {
                //tout afficher
                this.elements.allowed_countries.select('option').invoke('show');
            } else if (response.found == 'restriction') {
                //afficher uniquement les pays retournés

                this.elements.allowed_countries.select('option').invoke('hide');
                response.countries.each(function (elmt) {
                    this.elements.allowed_countries.select('option[value="' + elmt.label + '"]').invoke('show');
                }.bind(this));
            } else {
                //Tout cacher
                this.elements.allowed_countries.select('option').invoke('hide');
            }

            if ($('allowspecific').value == 0) {
                this.elements.allowed_countries.disabled = true;
            } else {
                this.elements.allowed_countries.disabled = false;
            }
        }
    },
    loadLogo: function (val) {
        var urlLogo = this.urls.logo;
        new Ajax.Request(urlLogo, {
            method: 'post',
            onSuccess: this.displayLogo.bind(this),
            onFailure: this.ajaxFailure.bind(this),
            parameters: {
                value: val
            }
        });
    },
    displayLogo: function (transport) {
        if (transport && transport.responseText) {
            try {
                response = eval('(' + transport.responseText + ')');
            } catch (e) {
                response = {};
            }
        }

        if (response != null && response != '') {
            this.elements.logo_url.value = response;
            if (this.elements.logo_image)
                this.elements.logo_image.src = response;
        }
    },
    _disabledAllOptions: function () {
        this.options.each(function (opt) {
            this._hideShowGroup(opt, true);
        }.bind(this));
    },
    _hideShowGroup: function (element, choice) {
        if (choice) {
            element.previous('div').hide();
            element.hide();
        } else {
            element.previous('div').show();
            element.show();
        }
        this._disableEnableAll(element, choice);
    },
    _disableEnableAll: function (element, isDisabled, exception) {
        var descendants = element.descendants();
        for (var k in descendants) {
            if (descendants[k].id != exception) {
                if (descendants[k].tagName != 'OPTION') {
                    descendants[k].disabled = isDisabled;
                }
            }

        }
        element.disabled = isDisabled;
    },
    ajaxFailure: function () {
        location.reload();
    }
};

document.observe("dom:loaded", function () {
    new Be2bill();
});