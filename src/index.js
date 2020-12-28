/*
 * Copyright (c) 2019 TASoft Applications, Th. Abplanalp <info@tasoft.ch>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

import {FetchQuery} from "./image-tool/fetch-query";
import {
    BootstrapCarouselQueryTarget, CallbackQueryTarget,
    CaptureQueryTarget,
    ChainQueryTarget,
    QueryTarget,
    TemplateQueryTarget
} from "./image-tool/query-target";
import {ChangeMainQuery, ChangeNameQuery, ChangeOrderQuery, DropImageQuery} from "./image-tool/change-query";
import {targets} from "./image-tool/targets";
import _config from "./image-tool/config-image-capture"
import {PutFromCaptureQuery, PutRefImageQuery} from "./image-tool/put-query";
import {Result} from "./image-tool/result";

((S)=>{
    Object.assign(S, {
        ImageTool: {
            TARGETS: targets,
            CAPTURE_CONFIG: _config
        },
        Query: {
            FetchQuery,
            ChangeNameQuery,
            ChangeOrderQuery,
            ChangeMainQuery,
            PutRefImageQuery,
            PutFromCaptureQuery,
            DropImageQuery,
            Result
        },
        Target: {
            QueryTarget,
            ChainQueryTarget,
            TemplateQueryTarget,
            CaptureQueryTarget,
            BootstrapCarouselQueryTarget,
            CallbackQueryTarget
        }
    })
})(window.Skyline);