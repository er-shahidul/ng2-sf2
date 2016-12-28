import { CommonModule } from '@angular/common';
import { UserListComponent } from './user-list/user-list.component';
import { NewUserComponent } from './new-user/new-user.component';
import {HttpModule} from "@angular/http";
import {UserService} from "./user.service";
import {NgModule} from "@angular/core";
import {RoutingModule} from "./user-routing.module";
import {FormsModule, ReactiveFormsModule} from "@angular/forms";
import {ErrorBlockComponent} from "../error-block/error-block.component";

@NgModule({
  imports: [
    CommonModule, HttpModule, RoutingModule, ReactiveFormsModule, FormsModule
  ],
  declarations: [UserListComponent, NewUserComponent, ErrorBlockComponent],
  providers: [UserService]
})
export class UserModule {}
