import Skyline from "../skyline"

export default () => {
    const i18n = Skyline.ImageCapture.TRANSLATIONS;

    return {
        PROPERTIES: {
            SLUG: new Skyline.Property.SlugTextFieldProperty({
                name: "slug",
                label: i18n.property_slug_label,
                placeholder: i18n.property_slug_placeholder,
                icon: 'fa-globe'
            }),
            CAPTION: new Skyline.Property.TextFieldProperty({
                name: 'caption',
                label: i18n.property_caption_label,
                placeholder: i18n.property_caption_placeholder,
                icon: 'fa-tag'
            }),
            ALT: new Skyline.Property.TextFieldProperty({
                name: 'alt',
                label: i18n.property_alt_label,
                placeholder: i18n.property_alt_placeholder,
                icon: 'fa-bolt'
            }),

            install(IK) {
                IK.addProperty(this.SLUG);
                IK.addProperty(this.CAPTION);
                IK.addProperty(this.ALT);
            }
        },
        OPTIONS: {
            SCALE: new Skyline.Option.Option({id: 1, label: i18n.option_scale_to_best, checkedByDefault: true}),
            PREVIEW: new Skyline.Option.Option({id: 2, label: i18n.option_render_preview, checkedByDefault: true}),
            MAIN: new Skyline.Option.Option({id: 4, label: i18n.option_make_main, checkedByDefault: false}),
            WATERMARK: new Skyline.Option.DisabledOption({id: 8, label: i18n.option_make_watermark, checkedByDefault: false}),

            install(IK) {
                IK.addOption(this.SCALE);
                IK.addOption(this.PREVIEW);
                IK.addOption(this.MAIN);
                IK.addOption(this.WATERMARK);
            }
        }
    }
};
