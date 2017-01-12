import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { CrudRoutingModule } from './crud-routing.module';
import { ListEntityComponent } from './list-entity/list-entity.component';
import { FormEntityComponent } from './form-entity/form-entity.component';
import {HttpModule} from "@angular/http";

@NgModule({
  imports: [
    CommonModule,
    CrudRoutingModule,
    HttpModule
  ],
  declarations: [ ListEntityComponent, FormEntityComponent]
})
export class CrudModule {}
