<template>
    <div>
        <h1>{{ this.store.gallery.name}}</h1>
        <image-overlay 
            v-if="isFullscreen" 
            
            v-bind:file="file" 
            v-bind:file-list="store.gallery.files"
            
            v-on:close="onCloseFile"
            v-on:open-file="onOpenFile" 
        ></image-overlay>
        <image-gallery 
            v-bind:gallery="store.gallery" 
            v-bind:tile-min-height="200"
            v-bind:tile-target-height="230"
            v-bind:tile-max-height="250"
            
            v-on:open-file="onOpenFile" 
        ></image-gallery>
    </div>
</template>

<script>

import ImageGallery from './ImageGallery.vue';
import ImageOverlay from './ImageOverlay.vue';

export default {
    name: "App",
    components: { ImageGallery, ImageOverlay },
    
    props:{
        
    },
    inject: [ "store" ],
    
    data: function(){ 
        return {
            file: null,
        }; 
    },
    
    computed:{
        isFullscreen: function(){
            return this.file !== null;
        }
    },
    
    created: function(){
    },
    
    methods: {
        onOpenFile(file){
            this.file = file;
        },
        onCloseFile(){
            this.file = null;
        }
    },
}
</script>

<style>
</style>
