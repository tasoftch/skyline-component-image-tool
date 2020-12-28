import templates from "./templates";
import $ from "../jquery";

export class QueryTarget {
    renderResult(result) {}
    renderError(error) {}
}

const parsedTmp = (temp, i, nr, main, ID) => {
    temp = temp.replace(/&amp;src;/ig, i.src);
    temp = temp.replace(/&amp;alt;/ig, i.alternative || "");
    temp = temp.replace(/&amp;caption;/ig, i.caption || "");
    temp = temp.replace(/&amp;nr;/ig, nr);
    temp = temp.replace(/&amp;preview;/ig, i.thumb || i.src);

    if(main)
        temp = temp.replace(/&amp;main:(\w+);/ig, '$1');
    else
        temp = temp.replace(/&amp;main:(\w+);/ig, '');

    return temp.replace(/&amp;id;/ig, ID);
};

export class ChainQueryTarget extends QueryTarget {
    constructor(...targets) {
        super();
        this.targest = targets;
    }

    renderResult(result) {
        this.targest.forEach((t)=>t.renderResult(result));
    }

    renderError(error) {
        this.targest.forEach((t)=>t.renderError(error));
    }

    get request() { return this._request; }
    set request(r) { this.targest.forEach((t)=>t.request=r);this._request=r; }
}

export class CaptureQueryTarget extends QueryTarget {
    constructor({progress, done, error}) {
        super();
        this.progress = progress;
        this.done = done;
        this.error = error;
    }

    set request(r) {
        this._request = r;
        r.upload((p)=>{
            this.progress(p/100);
        }).success(()=>{
            this.done();
        }).error((err) => {
            this.error(err.message);
        })
    }
    get request() { return this._request; }
}

export class TemplateQueryTarget extends QueryTarget {
    static get templates() { return templates; }

    constructor(container, reference, {
        template,
        error = undefined
    }) {
        super();
        if(typeof template === 'string') {
            const tmp = template;
            template = (img, nr, main, ID) => {
                const t = $(tmp).html();
                return parsedTmp(t ? t : "", img, nr, main, ID);
            }
        }
        if(typeof template !== 'function')
            throw new Error("tempate must be a callback or query string");

        container = $(container);

        this.renderResult = (result) => {
            this.clearContainer({container});

            let images = null, main_img = 0;
            result.references.forEach(r=>{
                if(r.slug === reference) {
                    images = r.images.map(i=>{
                        const im= result.images[i];
                        im.id=i;
                        return im;
                    });
                    main_img = r.main
                }
            })

            if(images) {
                let cnt = 0;
                images.forEach((i,k)=>{
                    const t = template(i, cnt, main_img === i.id, i.id);
                    this.bindRenderedTemplate({t, image:i, nr:cnt});
                    this.appendRenderedTemplate({container, image:i, t, nr:cnt});
                    cnt++;
                });
            }
        }
        this.renderError = (err) => {
            if(typeof error === 'function')
                error(err);
            else if(typeof error === 'object' && typeof error.renderError === 'function')
                error.renderError(err);
            else
                console.error(err);
        }
    }


    clearContainer({container}) {
        container.html("");
    }

    bindRenderedTemplate({t, image, nr, main}) {
    }

    appendRenderedTemplate({container, t, image, nr}) {
        container.append(t);
    }
}

export class BootstrapCarouselQueryTarget extends QueryTarget {
    constructor(container, reference, {
        controls= true,
        indicators = true,
        previews = false,
        captions = false,

        prev_title = 'Previous',
        next_title = "Next"
                }) {
        super();

        const $cont = $(container);

        const $indi = $cont.find(".carousel-indicators");
        const $inner = $cont.find(".carousel-inner");

        const tempImg = $cont.find(".templates .carousel-item-temp").html();
        const indiTemp = $cont.find(".templates .carousel-indicator-temp").html();


        let ID = $cont.attr('id');
        if(!ID)
            throw new Error("Carousel must have an id attribute.");



        this.renderResult = (result) => {
            $indi.find("li").remove();
            $inner.find(".carousel-item").remove();

            let images = null, main_img = 0;
            result.references.forEach(r=>{
                if(r.slug === reference) {
                    images = r.images.map(i=>{
                        return result.images[i];
                    });
                    main_img = r.main
                }
            })

            if(images) {
                let cnt = 0;
                images.forEach(i=>{
                    $inner.append(parsedTmp(tempImg, i, cnt));

                    if($indi.length) {
                        $indi.append(parsedTmp(indiTemp, i, cnt))
                    }
                    cnt++;
                });
            }

            $indi.find("li:first-child").addClass("active");
            $inner.find(".carousel-item:first-child").addClass("active");
        }
    }
}

export class CallbackQueryTarget extends QueryTarget {
    constructor({success, error}) {
        super();
        if(typeof success === 'function')
            this.renderResult = (r) => success(r);
        if(typeof error === 'function')
            this.renderError = (e) => error(e);
    }
}



