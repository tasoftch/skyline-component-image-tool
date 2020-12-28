
export default {
    simple_image_template: ({src, alt}) =>{ return "<img src='"+src+"' alt='"+alt+"'>"},
    bootstrap_image_template: ({src, alt, caption, isPreview}) => {isPreview=isPreview ? 'thumbnail' : ""; return "<figure class=\"figure mb-lg-0 mb-4\">\n" +
        "    <img class=\"img-fluid rounded shadow "+isPreview+"\" src=\""+src+"\" alt=\""+alt+"\">\n" +
        "    <figcaption class=\"figure-caption text-muted text-center\">"+caption+"</figcaption>\n" +
        "</figure>"}
};
