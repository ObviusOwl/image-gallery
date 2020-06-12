
export class Api{
    
    constructor(){
        
    }
    
    encodeQueryString( data ){
        var p = "";
        var keys = Object.keys(data);
        for( var i=0; i<keys.length; i++){
            var k = keys[i];
            p = p + encodeURIComponent(k) + "=" + encodeURIComponent( data[k] );
            if( i < keys.length - 1 ){
                p = p + "&";
            }
        }
        return p;
    }
    
    apiCall( opts ) {
        if( ! ('url' in opts) ){
            throw Error("opts.url parameter is required");
        }
        if( ! ('method' in opts) ){ opts.method = "GET"; }
        if( ! ("headers" in opts) ){ opts.headers = {}; }
        opts.headers[ 'Content-Type' ] = "application/json";

        if( 'query' in opts ){
            var queryString = this.encodeQueryString( opts.query );
            if( queryString != "" ){
                opts.url = opts.url + "?" + queryString;
            }
        }

        return new Promise(function (resolve, reject) {
            var xhr = new XMLHttpRequest();
            xhr.open(opts.method, opts.url);
            xhr.responseType = "text";
            
            for( var h in Object.keys(opts.headers) ){
                xhr.setRequestHeader(h, opts.headers[h] );
            }
            
            xhr.onload = function () {
                var data = JSON.parse(xhr.response);
                if (this.status >= 200 && this.status < 300) {
                    resolve( data );
                }else {
                    reject( {status: this.status, statusText: xhr.statusText, data: data} );
                }
            };
            
            xhr.onerror = function () {
                reject( { status: this.status, statusText: xhr.statusText, data:null} );
            };
            
            if( opts.method.toLowerCase() == "get" || ! ("data" in opts) ){
                xhr.send();
            }else if( typeof(opts.data) === 'object' ){
                xhr.send( JSON.stringify(opts.data) );
            }else{
                xhr.send( opts.data );
            }
        });
    }
    
    getGallery(id){
        let opts = {
            url : `api.php/galleries/${id}`,
            method: "GET",
        };
        return this.apiCall(opts);
    }
    
}