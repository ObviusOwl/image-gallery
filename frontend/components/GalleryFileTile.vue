<script>

export default {
    name: "GalleryFileTile",
    
    props: {
        file: { type: Object, required: true },
    },
    
    data: function() {
        return {
        };
    },
    
    computed: {
        thumbs: function(){
            let ta = this.file.thumbnails.filter( t => t.size_name === "M" );
            if( ta.length == 0 ){
                return [];
            }else if( ta.length == 1 ){
                return [ ta[0], ta[0], ta[0], ta[0] ];
            }else if( ta.length == 2 ){
                return [ ta[0], ta[1], ta[0], ta[1] ];
            }else if( ta.length == 3 ){
                return [ ta[0], ta[1], ta[2], ta[2] ];
            }else{
                return [ ta[0], ta[1], ta[2], ta[3] ];
            }
        }
    },
    
    methods: {
    }
}
</script>

<template>
    <div class="gallery-file-tile">
        <div class="gallery-file-tile__thumb-cont" >
            <div class="gallery-file-tile__title">{{file.name}}</div>
            <img v-for="(thumb, idx) in thumbs" v-bind:key="idx.toString() + thumb.file_url" 
                class="gallery-file-tile__thumb" v-bind:src="thumb.file_url"
            />
        </div>
    </div>
</template>

<style>
.gallery-file-tile__thumb-cont{
    display:flex;
    flex-wrap: wrap;
    height: 100%;
    width:100%;
    position:relative;
}
.gallery-file-tile__thumb{
    height: calc(50% - 2px);
    width: calc(50% - 2px);
    object-fit: cover;
    border: 1px solid white;
}
.gallery-file-tile{
    overflow: hidden;
}
.gallery-file-tile__title{
	position: absolute;
	bottom: 0px;
    padding: 0 .5em;
    width: calc(100% - 1em);
    
	background-color: rgba(0,0,0,0.5);
	color: white;
	font-style: italic;
    line-height: 1.5em;
    font-size: 1.2em;
}

</style>
