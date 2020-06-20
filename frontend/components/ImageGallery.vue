<script>
import GalleryTile from './GalleryTile.vue';

export default {
    name: "ImageGallery",
    components: { GalleryTile },
    
    props: {
        gallery: { type: Object, required: true },
        tileMinHeight: { type: Number, default: 200 },
        tileMaxHeight: { type: Number, default: 250 },
        tileTargetHeight: { type: Number, default: 250 },
    },
    inject: [ "store" ],
    
    data: function(){
        return {
            tileSizes: []
        };
    },
    
    beforeUpdate: function(){
        this.tileSizes = this.recalcRows();
    },
    
    mounted: function(){
        this.tileSizes = this.recalcRows();
    },

    created() {
        window.addEventListener("resize", this.onWindowResize);
    },
    
    destroyed() {
        window.removeEventListener("resize", this.onWindowResize);
    },
    
    methods: {
        getTileWidth: function(idx){
            return this.tileSizes[idx] != null ? this.tileSizes[idx].width : 100;
        },
        getTileHeight: function(idx){
            return this.tileSizes[idx] != null ? this.tileSizes[idx].height : 150;
        },
        
        onWindowResize: function(){
            this.$forceUpdate();
        },
        
        recalcRows: function(){
            if( this.gallery.files === undefined ){
                return [];
            }
            
            let ratios = this.gallery.files.map( f => {
                let def = 3/2;
                if( f.type.startsWith("image") ){
                    return f.aspect_ratio != 0 ? f.aspect_ratio : def;
                }else if( f.type == "application/x.image-gallery" ){
                    let ta = f.thumbnails.filter( t => t.size_name === "M" );
                    if( ta.length == 0 ){
                        return def;
                    }else if( ta.length == 1 ){
                        ta = [ ta[0], ta[0], ta[0], ta[0] ];
                    }else if( ta.length == 2 ){
                        ta = [ ta[0], ta[1], ta[0], ta[1] ];
                    }else if( ta.length == 3 ){
                        ta = [ ta[0], ta[1], ta[2], ta[2] ];
                    }else{
                        ta = [ ta[0], ta[1], ta[2], ta[3] ];
                    }
                    let w = ta.map( t => t.width ).reduce( (s, v) => s+v ) / 2;
                    let h = ta.map( t => t.height ).reduce( (s, v) => s+v ) / 2;
                    return h != 0 ? w/h : def;
                }
                return def;
            } );
            
            let sizes = [];
            let minHeight = this.tileMinHeight;
            let targetHeight = this.tileTargetHeight;
            let maxHeight = this.tileMaxHeight;
            let spaceWidth = 10;
            let maxWidth = this.$el.clientWidth - 10;
            
            var sliceRow = ( (s, e) => ratios.slice(s, e) );
            var sumArr = ( row => row.reduce( (a, b) => a+b ) );
            
            var s = 0; // start of row index icluding
            var e = 1; // end of row index excluding
            while(e < ratios.length){
                let currWidth = 0;
                let row = [];

                // greedy select tiles assuming max height
                while( e < ratios.length && currWidth < maxWidth ){
                    e++;
                    row = sliceRow(s, e);
                    currWidth = Math.ceil(targetHeight * sumArr(row)) + (spaceWidth * row.length);
                }
                
                let height = Math.floor( (maxWidth - (spaceWidth * row.length)) / sumArr(row) );
                if( height < minHeight ){
                    height = targetHeight;
                    e--;
                    row = sliceRow(s, e);
                }
                
                if( e <= s  ){
                    e = s+1;
                    row = sliceRow(s, e);
                }
                
                height = Math.floor( (maxWidth - (spaceWidth * row.length)) / sumArr(row) );
                height = Math.min(height, maxHeight);
                
                sizes.push( ... row.map( r => ({ height: height, width: Math.floor(height*r) }) ));
                s = e;
            };
            return sizes;
        },
        
        onTileClick(file){
            if( file.type == "application/x.image-gallery" ){
                this.$emit("open-gallery", file);
            }else{
                this.$emit("open-file", file);
            }
        }
    },

}
</script>

<template>
    <div class="image-gallery">
        <div class="image-gallery__tile-list" ref="tileList">
            <gallery-tile 
                v-for="(file, idx) in gallery.files" v-bind:key="idx.toString() + file.id.toString()" 
                v-on:click.native="onTileClick(file)"
                v-bind:file="file"
                v-bind:width="getTileWidth(idx)"
                v-bind:height="getTileHeight(idx)"
                class="image-gallery__tile"
            ></gallery-tile>
        </div>
    </div>
</template>


<style>
.image-gallery__tile-list {
    display: flex;
    flex-wrap: wrap;
}
.image-gallery__tile{
    margin: 15px 5px;
	box-shadow: rgb(103, 103, 103) 0px 0px 3px;
    background-color: rgb(242, 242, 242);
    cursor: pointer;
}
</style>
