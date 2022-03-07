import * as noUiSlider from 'materialize-css/extras/noUiSlider/nouislider';

const priceMinInput = $('#priceMin');
const priceMaxInput = $('#priceMax');

$(document).ready(function() {

    // inicializace slideru ceny v katalogu
    const priceMin = parseFloat(priceMinInput.data('price-min'));
    const priceMax = parseFloat(priceMaxInput.data('price-max'));

    const priceMinCurrent = parseFloat(priceMinInput.val());
    const priceMaxCurrent = parseFloat(priceMaxInput.val());

    const slider = document.getElementById('catalog-price-slider');
    if(slider)
    {
        noUiSlider.create(slider, {
            start: [priceMinCurrent, priceMaxCurrent],
            connect: true,
            tooltips: false,
            step: 1,
            orientation: 'horizontal',
            range: {
                'min': priceMin,
                'max': priceMax
            },
            format: wNumb({
                decimals: 0
            })
        });

        // update min. hodnoty slideru po změně inputu
        priceMinInput.change(function() {
            slider.noUiSlider.set([parseFloat(priceMinInput.val()), parseFloat(priceMaxInput.val())]);
        });

        // update max. hodnoty slideru po změně inputu
        priceMaxInput.change(function() {
            slider.noUiSlider.set([parseFloat(priceMinInput.val()), parseFloat(priceMaxInput.val())]);
        });

        // update inputů při posouvání
        slider.noUiSlider.on('update', inputsUpdate);
    }
});

function inputsUpdate(values)
{
    const newMin = parseFloat(values[0]).toFixed(0);
    const newMax = parseFloat(values[1]).toFixed(0);

    priceMinInput.val(newMin);
    priceMaxInput.val(newMax);
}