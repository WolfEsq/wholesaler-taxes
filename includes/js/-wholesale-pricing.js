console.log("-wholesale-pricing.js");

// console.log( jQuery( 'input[name=lithium-register-tax-id]' ).val() );

// jQuery( 'input[name=lithium-register-tax-id]' ).css('color', 'red');

// jQuery( 'input[name=lithium-register-tax-id]' ).focus( function() {
//     console.log('in');
// }).blur(function() {
//     console.log( jQuery( 'input[name=lithium-register-tax-id]' ).val() );
// });

jQuery( 'input[name=lithium-register-tax-id]' ).focusout(function() {
    console.log( jQuery( 'input[name=lithium-register-tax-id]' ).val() );
    var lithium_reg_tax_id = jQuery( 'input[name=lithium-register-tax-id]' ).val();
    if ( lithium_reg_tax_id != "" ) {
        executePHP( 'update_tax_id|' + lithium_reg_tax_id + '|endFuncNowOk' );
    }
    else {
        //  executePHP( 'update_tax_id_to_value||endFuncNowOk' );
    }
    executePHP('update_taxes');
});

function endFuncNowOk(){
    console.log("it is finished, it is through");
}
// function changeTaxRate
