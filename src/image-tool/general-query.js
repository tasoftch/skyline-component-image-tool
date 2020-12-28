import {QueryTarget} from "./query-target";
import api from "../api";
import {Result} from "./result";

export  class GeneralQuery {
    static get SELECT_ALL() { return 2047; }
    static get SELECT_NECESSARY() {
        return GeneralQuery.SELECT_RESOLVED_URI |
            GeneralQuery.SELECT_REFERENCE |
            GeneralQuery.SELECT_PREVIEW |
            GeneralQuery.SELECT_ALTERNATIVE |
            GeneralQuery.SELECT_IMAGES
    }

    static get SELECT_SCOPE() { return 512; }
    static get SELECT_REFERENCE() { return 1024; }
    static get SELECT_IMAGES() { return 128; }

    static get SELECT_ID() { return 4; }
    static get SELECT_PREVIEW() { return 2; }
    static get SELECT_CAPTION() { return 8; }
    static get SELECT_ALTERNATIVE() { return 16; }


    static get SELECT_NAME() { return 32; }
    static get SELECT_DESCRIPTION() { return 64; }

    static get SELECT_LINKED() { return 1; }
    static get SELECT_RESOLVED_URI() {return 256;}



    constructor({scope = null, reference = null, image = null}) {
        let t = scope ? 1 : 0;
        t += image ? 1 : 0;
        t += reference ? 1 : 0;

        if(t < 1)
            throw new Error("Must query a scope, reference or an image.");
        if(t > 1)
            throw new Error("Can only query a scope, reference or an image");
    }

    run(target, resultObj) {
        if(!target && !target instanceof QueryTarget)
            throw new Error("Target must be instance of Skyline.QueryTarget");

        const fd = new FormData();
        const tg = this.target;
        if(!tg)
            throw new Error("Subclass must specify an api target uri");

        this.adjustFormData(fd);

        target.request = this.request = api.post( tg, fd )
            .success((data) => target ? target.renderResult(resultObj ? new Result( data ) : data) : null )
            .error((data)=>target ? target.renderError(data) : null);
    }

    get target() { return null; }

    adjustFormData(fd) {}
}
