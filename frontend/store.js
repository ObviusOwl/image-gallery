//import Vue from 'vue';

import { Api } from './api.js';

export class Store{
    
    constructor(){
        this.api = new Api();
        this.gallery = {};
    }
    
    loadGallery(id){
        this.api.getGallery(id).then((data) => {
            console.log("loaded gallery");
            console.log(data);
            this.gallery = data;
        }).catch((err) => {
            // todo show ui message
            console.log( "error loading gallery: " + err );
        });
    }
    
    setGallery(data){
        this.gallery = data;
    }
    
}