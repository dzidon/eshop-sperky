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

    //
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
        M.Modal.getInstance($('#modal-loader')).open();

        $.post({
            url: formCartInsert.attr('action'),
            data: formCartInsert.serialize(),
            dataType: 'json',
        })
        .done(function (data)
        {
            console.log(data)
        })
        .fail(function (jqXHR)
        {
            const data = jqXHR['responseJSON'];
            if (Array.isArray(data['errors']) && data['errors'].length > 0)
            {
                const errors = data['errors'].join('<br>');
                $('#modal-error-text').html(errors);
            }
            else
            {
                $('#modal-error-text').text('Nepodařilo se vložit produkt do košíku, zkuste to prosím znovu.')
            }
            M.Modal.getInstance($('#modal-error')).open();
        })
        .always(function ()
        {
            M.Modal.getInstance($('#modal-loader')).close();
        });
    });
});