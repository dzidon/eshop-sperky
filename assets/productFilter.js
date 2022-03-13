import * as noUiSlider from 'materialize-css/extras/noUiSlider/nouislider';

const priceMinInput = $('#priceMin');
const priceMaxInput = $('#priceMax');

$(document).ready(function() {

    filterOpenOnLargeScreen();

    /*
        Slider cen v katalogu
     */
    let priceMin = parseFloat(priceMinInput.data('price-min'));
    let priceMax = parseFloat(priceMaxInput.data('price-max'));

    let priceMinCurrent = priceMin;
    let priceMaxCurrent = priceMax;

    if ($.isNumeric(priceMinInput.val()))
    {
        priceMinCurrent = parseFloat(priceMinInput.val());
    }

    if ($.isNumeric(priceMaxInput.val()))
    {
        priceMaxCurrent = parseFloat(priceMaxInput.val());
    }

    const slider = document.getElementById('catalog-price-slider');
    if (slider)
    {
        let disable = false;
        if (priceMin === priceMax) // nouislider neumí přijmout rovnající se min. a max. hodnoty
        {
            disable = true;

            priceMin = 0;
            priceMax = 1;
            priceMinCurrent = priceMin;
            priceMaxCurrent = priceMax;
        }

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

        if (disable)
        {
            slider.setAttribute('disabled', 'true');
        }
        else
        {
            // update min. hodnoty slideru po změně inputu
            priceMinInput.change(sliderUpdate(slider, priceMin, priceMax));

            // update max. hodnoty slideru po změně inputu
            priceMaxInput.change(sliderUpdate(slider, priceMin, priceMax));

            // update inputů při posouvání
            slider.noUiSlider.on('update', inputsUpdate);

            // nastavení focusu na odpovídající input
            slider.noUiSlider.on('start', priceInputSetFocus);
            slider.noUiSlider.on('change', priceInputSetFocus);
        }
    }
});

function inputsUpdate(values)
{
    const newMin = parseFloat(values[0]).toFixed(0);
    const newMax = parseFloat(values[1]).toFixed(0);

    priceMinInput.val(newMin);
    priceMaxInput.val(newMax);

    M.updateTextFields();
}

function sliderUpdate(slider, priceMin, priceMax)
{
    return function ()
    {
        let priceMinToSet = priceMin;
        let priceMaxToSet = priceMax;

        if ($.isNumeric(priceMinInput.val()))
        {
            priceMinToSet = parseFloat(priceMinInput.val());
        }

        if ($.isNumeric(priceMaxInput.val()))
        {
            priceMaxToSet = parseFloat(priceMaxInput.val());
        }

        slider.noUiSlider.set([priceMinToSet, priceMaxToSet]);
    }
}

function priceInputSetFocus(values, handle)
{
    if (handle === 0 && priceMinInput)
    {
        priceMinInput.focus();
    }
    else if (handle === 1 && priceMaxInput)
    {
        priceMaxInput.focus();
    }
}

function filterOpenOnLargeScreen()
{
    const filter = document.getElementById('product-filter-collapsible');
    const instance = M.Collapsible.init(filter, {inDuration: 0, outDuration: 0});
    if(window.innerWidth > 992)
    {
        instance.open();
    }
}