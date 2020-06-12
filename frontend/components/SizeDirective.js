
const fixedSize = function(el, arg, value){
    var key = ( arg === "height" ? "height" : "width" );
    if( typeof value !== "string" ){
        value = `${value}px`;
    }
    el.style[key] = value;
}

export default {
    name: "size",

    bind: function(el, binding, vnode){
        fixedSize(el, binding.arg, binding.value);
    },
    
    update: function(el, binding, vnode, oldVnode){
        if( binding.value !== binding.oldValue ){
            fixedSize(el, binding.arg, binding.value);
        }
    },
    
}