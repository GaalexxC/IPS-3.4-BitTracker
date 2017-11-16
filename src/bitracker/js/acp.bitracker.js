/************************************************/
/* IPB3 Javascript								*/
/* -------------------------------------------- */
/* acp.homepage.js - Homepage javascript 		*/
/* (c) IPS, Inc 2008							*/
/* -------------------------------------------- */
/* Author: Brandon Farber						*/
/************************************************/

ACPbitracker = {
	
	/*------------------------------*/
	/* Constructor 					*/
	init: function()
	{
		Debug.write("Initializing acp.bitracker.js");

		document.observe("dom:loaded", function(){
			if( $('mem_name') )
			{
				this.autoComplete = new ipb.Autocomplete( $('mem_name'), { multibox: false, url: acp.autocompleteUrl, templates: { wrap: acp.autocompleteWrap, item: acp.autocompleteItem } } );
			}
			else if( $('modmid') )
			{
				this.autoComplete = new ipb.Autocomplete( $('modmid'), { multibox: false, url: acp.autocompleteUrl, templates: { wrap: acp.autocompleteWrap, item: acp.autocompleteItem } } );
			}
			else if( $('member') )
			{
				if( $('piechart') )
				{
					Event.observe( window, 'load', function() {
						this.autoComplete = new ipb.Autocomplete( $('member'), { multibox: false, url: acp.autocompleteUrl, templates: { wrap: acp.autocompleteWrap, item: acp.autocompleteItem } } );
					}.bind(this) );
				}
				else
				{
					this.autoComplete = new ipb.Autocomplete( $('member'), { multibox: false, url: acp.autocompleteUrl, templates: { wrap: acp.autocompleteWrap, item: acp.autocompleteItem } } );
				}
			}
		});
	},
	
	confirmDelete: function( catid )
	{
		if( catid < 1 )
		{
			alert( "Category id missing" );
		}
		else
		{
			acp.confirmDelete( ipb.vars['app_url'].replace(/&amp;/g, '&' ) + 'module=categories&section=categories&code=dodelete&c=' + catid, "Are you sure you wish to delete this category and all of it's contents? There will be no other confirmation screens, and you cannot undo this action!" );
		}
	},
	
	confirmEmpty: function( catid )
	{
		if( catid < 1 )
		{
			alert( "Category id missing" );
		}
		else
		{
			acp.confirmDelete( ipb.vars['app_url'].replace(/&amp;/g, '&' ) + 'module=categories&section=categories&code=doempty&c=' + catid, "Are you sure you wish to empty this category? There will be no other confirmation screens, and you cannot undo this action!" );
		}
	}

};

ACPbitracker.init();