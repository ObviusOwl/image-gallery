<script>
import ImageFileTile from './ImageFileTile.vue';
import GalleryFileTile from './GalleryFileTile.vue';
import SizeDirective from './SizeDirective.js'

export default {
    name: "GalleryTile",
    components: { ImageFileTile, GalleryFileTile },
    directives: { "size": SizeDirective },
    
    props: {
        file: { type: Object, required: true },
        width: { type: Number, required: true },
        height: { type: Number, required: true },
    },
    
    data: function(){
        return {
        };
    },
    
    computed:{
        isImage: function(){
            return this.file.type !== undefined && this.file.type.startsWith("image");
        },
        isGallery: function(){
            return this.file.type === "application/x.image-gallery";
        }
    },
    
    beforeUpdate: function(){
    },
    
    mounted: function(){
    },
    
    methods: {
    },

}
</script>

<template>
    <div v-size:width="width" v-size:height="height">
        <image-file-tile 
            v-if="isImage"
            v-bind:file="file"
            class="gallery-tile__file"
        ></image-file-tile>
        <gallery-file-tile 
            v-else-if="isGallery"
            v-bind:file="file"
            class="gallery-tile__file"
        ></gallery-file-tile>

    </div>
</template>

<style>
.gallery-tile__file{
    width: 100%;
    height: 100%;
}
</style>
