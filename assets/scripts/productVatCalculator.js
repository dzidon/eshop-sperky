let inputPriceWithoutVat = undefined;
let inputVat = undefined;

let outputAmountVat = undefined;
let outputPriceWithVat = undefined;

$(document).ready(function() {
    inputPriceWithoutVat = $( ".js-input-price-without-vat" ).first();
    inputVat = $( ".js-input-vat" ).first();

    outputAmountVat = $( ".js-output-amount-vat" ).first();
    outputPriceWithVat = $( ".js-output-price-with-vat" ).first();

    if(inputPriceWithoutVat)
    {
        inputPriceWithoutVat.on('input', recalculateVatValues);
    }

    if(inputVat)
    {
        inputVat.change(recalculateVatValues);
    }

    recalculateVatValues();
});

//přepočítá hodnoty DPH
const recalculateVatValues = function() {

    if(inputPriceWithoutVat && inputVat && outputAmountVat && outputPriceWithVat)
    {
        const valInputPriceWithoutVat = inputPriceWithoutVat.val();
        const valInputVat = inputVat.val();

        if($.isNumeric(valInputPriceWithoutVat) && $.isNumeric(valInputVat))
        {
            const valOutputAmountVat = valInputPriceWithoutVat * valInputVat;
            outputAmountVat.text(valOutputAmountVat);
            outputPriceWithVat.text(parseInt(valInputPriceWithoutVat) + valOutputAmountVat);
        }
        else
        {
            outputAmountVat.text('?');
            outputPriceWithVat.text('?');
        }
    }
}