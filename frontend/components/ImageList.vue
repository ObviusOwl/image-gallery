<script>

export default {
    name: "ImageList",
    components: { },
    directives: { },
    
    props: {
        files: { type: Array, required: true },
    },
    
    data: function(){
        return {
        };
    },
    
    computed:{
    },
    
    beforeUpdate: function(){
    },
    
    mounted: function(){
    },
    
    methods: {
        fileThumbUrl: function(file){
            let thumbs = this.thumbnails.filter( t => t.size_name === "M" );
            return thumbs.length != 0 ? thumbs[0].file_url : "";
        },
        onThumbClick: function(idx){
            this.$emit("select", idx)
        },
        
        onScroll: function(evt){
            if( evt.deltaY == 0 ){
                return;
            }
            
            let offset = 50;
            let img = this.$refs.imageList.querySelector(".image-list__thumb");
            if( img !== null ){
                offset = img.clientWidth;
            }
            
            this.$refs.imageList.scrollLeft += evt.deltaY * offset;
            evt.preventDefault();
        }
    },

}
</script>

<template>
    <div class="image-list">
        <div class="image-list__cont" ref="imageList" v-on:wheel="onScroll($event)">
                <img 
                v-for="(file, idx) in files" v-bind:key="idx.toString() + file.id.toString()" 
                class="image-list__thumb" 
                v-bind:src="fileThumbUrl(file)" 
                v-on:click="onThumbClick(idx)"
                />
        </div>
    </div>
</template>

<style>

.image-list__cont{
    display:flex;
    flex-direction:row;
    flex-wrap: nowrap;
    padding-top: 3vh;
    margin-bottom: 1vh;
    
    overflow-x: auto;
}

.image-list__thumb{
    object-fit: cover;
    min-width: 15vh;
    height:15vh;
    margin-right: 1vh;
    cursor: pointer;
}

.image-list__thumb:hover{
    position:relative;
    bottom: 1.5vh;
    transform: scale(1.1);
    box-shadow: 0 0 3vh black;
}

</style>
