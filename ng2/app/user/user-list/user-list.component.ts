import { Component, OnInit } from '@angular/core';
import {User} from "../user";
import {Observable} from "rxjs";
import {UserService} from "../user.service";

@Component({
  selector: 'app-user-list',
  templateUrl: './user-list.component.html',
  styleUrls: ['./user-list.component.scss']
})
export class UserListComponent implements OnInit {
  users: User[];
  constructor(protected userService:UserService) { }

  getUsers() {
    this.userService.getUsers()
        .subscribe(users => this.users = users);
  }

  ngOnInit() {
    this.getUsers();
  }

}
