import { ajaxAddProductToCart } from './app';

$(document).ready(function() {

    // smooth scroll na celý popis po kliknutí na odkaz "(Celý popis)"
    $("#full-description-link").click(function() {
        const target = document.getElementById('full-description');
        target.scrollIntoView({
            behavior: 'auto',
            block: 'center',
            inline: 'center'
        });
    });

    // obrázky na stránce produktu
    $('.black-and-white-img:first').removeClass('black-and-white-img');

    const productImageLarge = $('#product-image-large');
    $('.product-image-small-link').click(function() {
        const clickedImage = $(this).children("img:first");
        const src = clickedImage.attr('src');
        productImageLarge.attr('src', src);

        $('.product-image-small-link').find('img').addClass('black-and-white-img');
        clickedImage.removeClass('black-and-white-img');
    });

    // nastavení hodnoty "1", pokud je tam něco jinýho po změně
    const quantityInput = $('#cart_insert_form_quantity');
    quantityInput.on('change', function()
    {
        if (!$.isNumeric(quantityInput.val()))
        {
            quantityInput.val('1');
        }
    });

    // formulář pro vložení do košíku
    const formCartInsert = $('#form-cart-insert');
    formCartInsert.on('submit', function(e)
    {
        e.preventDefault();
        ajaxAddProductToCart(formCartInsert.attr('action'), formCartInsert.serialize());
    });
});