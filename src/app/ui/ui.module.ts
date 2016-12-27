import { BrowserModule } from '@angular/platform-browser';
import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import {Ng2BootstrapModule} from "ng2-bootstrap";
import {NavComponent} from "./nav/nav.component";
import { NavItemComponent } from './nav/nav-item/nav-item.component';
import {Routes, RouterModule} from "@angular/router";


const routes: Routes = [

];

@NgModule({
  imports: [
    CommonModule,
    BrowserModule,
    Ng2BootstrapModule,
    RouterModule.forChild(routes)
  ],
  exports: [
    Ng2BootstrapModule,
    NavComponent,
    CommonModule,
    BrowserModule
  ],
  declarations: [
    NavComponent,
    NavItemComponent
  ]
})
export class UiModule { }
