import { BrowserModule } from '@angular/platform-browser';
import { NgModule } from '@angular/core';
import { FormsModule } from '@angular/forms';

import { AppComponent } from './app.component';
import {RoutingModule} from "./app-routing.module";
import {UiModule} from "./ui/ui.module";

@NgModule({
  declarations: [
    AppComponent
  ],
  imports: [
    FormsModule,
    UiModule,
    RoutingModule
  ],
  providers: [],
  bootstrap: [AppComponent]
})
export class AppModule { }
