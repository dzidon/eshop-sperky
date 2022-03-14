import * as noUiSlider from 'materialize-css/extras/noUiSlider/nouislider';

let searchPhraseInput;
let priceMinInput;
let priceMaxInput;
let sortByInput;
let categoriesInput;

const xhr = new XMLHttpRequest();

$(document).ready(function() {
    initialize();

    // Otevření filtru na velkých obrazovkách
    filterOpen(992);
});

function initialize()
{
    searchPhraseInput = $('#searchPhrase');
    priceMinInput = $('#priceMin');
    priceMaxInput = $('#priceMax');
    sortByInput = $('#sortBy');
    categoriesInput = $('[id^="categories"]');

    /*
        Update filtru
     */
    searchPhraseInput.on('change', catalogReset);
    sortByInput.on('change', catalogReset);
    priceMinInput.on('change', catalogReset);
    priceMaxInput.on('change', catalogReset);
    categoriesInput.on('change', catalogReset);

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
        let disablePriceSlider = false;
        if (priceMin === priceMax) // nouislider neumí přijmout rovnající se min. a max. hodnoty
        {
            disablePriceSlider = true;

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

        if (disablePriceSlider)
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
            slider.noUiSlider.on('change', inputsUpdate);

            // nastavení focusu na odpovídající input
            slider.noUiSlider.on('start', priceInputSetFocus);
            slider.noUiSlider.on('change', priceInputSetFocus);

            // odeslani formulare s filtrem po posunuti slideru
            slider.noUiSlider.on('change', catalogReset);
        }
    }
}

function inputsUpdate(values)
{
    const newMin = parseFloat(values[0]).toFixed(0);
    const newMax = parseFloat(values[1]).toFixed(0);

    priceMinInput.val(newMin);
    priceMaxInput.val(newMax);
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

function filterOpen(afterWidth)
{
    const filter = document.getElementById('product-filter-collapsible');
    const instance = M.Collapsible.init(filter, {inDuration: 0, outDuration: 0});
    if(window.innerWidth > afterWidth)
    {
        instance.open();
    }
}

function catalogReset()
{
    const modalLoaderElement = $('#modal-loader').modal({
        dismissible: false
    });
    M.Modal.getInstance(modalLoaderElement).open();

    $.get({
        url: '?' + $('#form-product-catalog').serialize(),
        dataType: 'html',
        xhr: function()
        {
            return xhr;
        },
        complete: function()
        {
            M.Modal.getInstance($('#modal-loader')).close();
        },
        success: function(data)
        {
            $('#product-catalog-container').html(data);
            initialize();
            filterOpen(0);
            window.history.replaceState({}, '', xhr.responseURL);

            $('select').formSelect();
            M.updateTextFields();
        },
        error: function()
        {
            M.Modal.getInstance($('#modal-error')).open();
        }
    });
}