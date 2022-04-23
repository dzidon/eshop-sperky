import * as noUiSlider from 'materialize-css/extras/noUiSlider/nouislider';
import { initializeAddToCartLinks, errorModalOpen, loaderOpen, loaderClose } from './app';

let searchPhraseInput;
let priceMinInput;
let priceMaxInput;
let sortByInput;
let categoriesInput;

let requestInProgress = false;
const xhr = new XMLHttpRequest();

$(document).ready(function()
{
    initialize();

    // Otevření filtru na velkých obrazovkách
    filterOpen(992);
});

$(window).bind("popstate", function()
{
    catalogResetUsingBackButton();
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
    searchPhraseInput.on('change', catalogResetUsingForm);
    sortByInput.on('change', catalogResetUsingForm);
    priceMinInput.on('change', catalogResetUsingForm);
    priceMaxInput.on('change', catalogResetUsingForm);
    categoriesInput.on('change', catalogResetUsingForm);

    $('#form-product-catalog').on('submit', function(e)
    {
        e.preventDefault();
        catalogResetUsingForm();
    });

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

            // odeslani formulare s filtrem po posunuti slideru
            slider.noUiSlider.on('change', catalogResetUsingForm);
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

function filterOpen(afterWidth)
{
    const filter = document.getElementById('product-filter-collapsible');
    const instance = M.Collapsible.init(filter, {inDuration: 0, outDuration: 0});
    if(window.innerWidth > afterWidth)
    {
        instance.open();
    }
}

function catalogResetUsingBackButton()
{
    catalogReset(document.location.href, false);
}

function catalogResetUsingForm()
{
    catalogReset('?' + $('#form-product-catalog').serialize(), true);
}

function catalogReset(url, addToHistory)
{
    if(requestInProgress)
    {
        return;
    }
    requestInProgress = true;

    loaderOpen('Aktualizuji výpis...');

    $.get({
        url: url,
        dataType: 'html',
        xhr: function()
        {
            return xhr;
        },
        complete: function()
        {
            loaderClose();
            requestInProgress = false;
        },
        success: function(data)
        {
            $('#product-catalog-container').html(data);
            initialize();
            initializeAddToCartLinks();
            filterOpen(0);

            $('select').formSelect();
            M.updateTextFields();

            if(addToHistory)
            {
                window.history.pushState({}, '', xhr.responseURL);
            }
        },
        error: function()
        {
            errorModalOpen('Nepodařilo se načíst katalog produktů, zkuste to prosím znovu.');
        }
    });
}