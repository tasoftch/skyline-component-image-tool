import {GeneralQuery} from "./general-query";
import {targets} from "./targets";

export class PutQuery extends GeneralQuery {
    static get OPTION_MAKE_SCALED_IMAGE() {return 1;}
    static get OPTION_MAKE_PREVIEW() { return 2; }
    static get OPTION_MAKE_MAIN_IMAGE() {return 4;}

    get target() {
        return targets.put_action_uri;
    }
}

export class PutRefImageQuery extends PutQuery {
    constructor({reference, file, slug = null, caption = null, alt = null, options = 3, additional=null}) {
        super({reference});
        this.adjustFormData = (fd)=>{
            fd.append("r", reference);
            fd.append("f", file);

            if(slug)
                fd.append("s", slug);
            if(options)
                fd.append("o", options);
            if(caption)
                fd.append("n", caption);
            if(alt)
                fd.append("d", alt);
            if(additional)
                fd.append("add", JSON.stringify( additional ));
        }
    }
}

export class PutFromCaptureQuery extends PutRefImageQuery {
    constructor(reference, {file, properties, options}) {
        let add = null;
        if(properties.translation) {
            add = {
                translation: properties.translation,
                scale: properties.scale,
                frame: properties.frame
            };
        }
        super({reference, file, slug:properties.slug, caption:properties.caption, alt:properties.alt, options, additional: add});
    }
}
