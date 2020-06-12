<script>
import ImageList from './ImageList.vue';

export default {
    name: "ImageOverlay",
    components: { ImageList },
    directives: { },
    
    props: {
        file: { type: Object, required: true },
        fileList: { type: Array, required: true },
    },
    
    data: function(){
        return {
        };
    },
    
    computed: {
        imageList: function(){
            return this.fileList.filter( f => f.type.startsWith("image") );
        }
    },
    
    beforeUpdate: function(){
    },
    
    mounted: function(){
    },
    
    methods: {
        onClose: function(){
            this.$emit("close");
        },
        onSelect: function(idx){
            this.$emit("open-file", this.imageList[idx]);
        },
    },

}
</script>

<template>
    <div class="image-overlay">
        <div class="image-overlay__cont"  >
            <div class="image-overlay__imgcont" v-on:click.self="onClose()">
            <img class="image-overlay__img" 
                v-bind:src="file.file_url" 
            />
            </div>
            <image-list class="image-overlay__file-list"
                v-bind:files="imageList"
                v-on:select="onSelect"
            ></image-list>
        </div>
    </div>
</template>

<style>
.image-overlay{
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    z-index: 10;
    background-color: rgba(0,0,0,0.9);
}

.image-overlay__img{
    max-height: 100%;
    max-width: 100%;
}

.image-overlay__imgcont{
    margin: 0 auto;
    height: 80vh;

    display: flex;
    justify-content: center;
    align-items: center;
}

.image-overlay__file-list{
    margin: 0 auto;
    width:100%;
    height: 20vh;
}

</style>
