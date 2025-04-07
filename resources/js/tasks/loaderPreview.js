import Mustache from "mustache";
import vueFormElements from "../next/libraries/vueFormElements";
import screenBuilder from "../next/screenBuilder";
import { setupMain } from "../next/setupMain";

window.Mustache = Mustache;

setupMain();
screenBuilder();
