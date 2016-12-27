import { CommonModule } from '@angular/common';
import { UserListComponent } from './user-list/user-list.component';
import { NewUserComponent } from './new-user/new-user.component';
import {HttpModule} from "@angular/http";
import {UserService} from "./user.service";
import {NgModule} from "@angular/core";
import {RoutingModule} from "./user-routing.module";

@NgModule({
  imports: [
    CommonModule, HttpModule, RoutingModule
  ],
  declarations: [UserListComponent, NewUserComponent],
  providers: [UserService]
})
export class UserModule {}
