
import {GeneralQuery} from "./general-query";
import {targets} from "./targets";

export class FetchQuery extends GeneralQuery {
    constructor({scope = null, reference = null, image = null, select = FetchQuery.SELECT_NECESSARY}) {
        super({scope, reference, image});


        this.adjustFormData = (fd) => {
            if(scope)
                fd.append('s', scope);
            if(reference)
                fd.append('r', reference);
            if(image)
                fd.append('i', image);

            fd.append("l", select);
        };
    }

    get target() {
        return targets.fetch_action_uri;
    }
}
