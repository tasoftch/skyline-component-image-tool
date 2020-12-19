
export class ImageQuery {
    static get SELECT_ALL() { return 255; }

    static get SELECT_SRC() { return 1; }
    static get SELECT_PREVIEW() { return 2; }
    static get SELECT_ID() { return 4; }
    static get SELECT_CAPTION() { return 8; }
    static get SELECT_ALTERNATIVE() { return 16; }

    static get SELECT_MAIN_IMAGE() { return 64; }
    static get SELECT_OTHER_IMAGES() { return 128; }


    constructor({scope = null, reference = null, select = ImageQuery.SELECT_ALL}) {
        if(!scope && !reference)
            throw new Error("Must query a scope or a reference");
        if(scope && reference)
            throw new Error("Can not query a scope and a reference");
    }
}
