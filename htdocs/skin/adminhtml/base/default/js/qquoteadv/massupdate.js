/**
 * Created by Bram van Dooren on 12-02-14.
 */

document.observe("dom:loaded", function () {

    $$("#mass_update_button").invoke('observe', 'click', function (event) {
        massUpdateAllowToQuote();
    });
});

function massUpdateAllowToQuote() {
    var url = $('mass_update_button').readAttribute('url');
    var quote_mode = $('qquoteadv_quote_advanced_mass_update_cart2quote_attributes').getValue();
    var ranges = $('qquoteadv_quote_advanced_mass_update_cart2quote_attribute_ranges').getValue();

    new Ajax.Request(url, {
        method: 'post',
        parameters: {quote_mode: quote_mode, ranges: ranges  },
        onSuccess: function (response) {
            //alert(response.responseText);
            location.reload();
        }
    });
}
