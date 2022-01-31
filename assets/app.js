import 'materialize-css/dist/css/materialize.min.css';
require('materialize-css/dist/js/materialize.min');

import './styles/app.css';


$(document).ready(function() {

    //materialize
    $('.sidenav').sidenav();
    $('.parallax').parallax();
    $('select').formSelect();
    $(".dropdown-trigger").dropdown({ hover: false });

    //dynamicke upravovani pro CollectionType
    document
        .querySelectorAll('.add_item_link')
        .forEach(btn => {
            btn.addEventListener("click", addFormToCollection)
    });
});

/*
* Přidá formulář do CollectionType
*/
const addFormToCollection = (e) => {
    const collectionHolder = document.querySelector('.' + e.currentTarget.dataset.collectionHolderClass);

    const item = document.createElement('li');

    item.innerHTML = collectionHolder
        .dataset
        .prototype
        .replace(
            /__name__/g,
            collectionHolder.dataset.index
        );

    collectionHolder.appendChild(item.firstElementChild);

    collectionHolder.dataset.index++;
};