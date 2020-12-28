
export class Result {
    constructor(result) {
        this.references = new Map();
        result.references.forEach(r=> {
            result.references.forEach(r=>{
                const images = new Map();
                r.images.forEach(i=>{
                    images.set(i, result.images[i]);
                });
                const o = Object.assign({}, r);
                o.images = images;
                this.references.set(r.slug, o);
            })
        })
    }
}
