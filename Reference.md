
# Reference

## Data Sources

### Jaws Alarm Definitions

Existing JAWS alarm definitions can be fetched from https://ace.jlab.org/jaws/ajax/list-alarms.
An example item from the array looks like:

```json
{
  "name": "0L02-7 GMES",
  "id": 7613,
  "action": {
    "name": "GMES",
    "id": 128
  },
  "device": null,
  "pv": "R027GMES",
  "maskedBy": null,
  "screenCommand": null,
  "locations": [
    {
      "name": "Injector",
      "id": 5,
      "weight": 5,
      "parent": 1
    }
  ]
}
```

### ALH Alarm Defintions

Definitions from ALH can be found in the set of files located at /cs/prohome/apps/m/makeALHConfig/4-1/src/bin/JAWS/alarms.
They are json-ish, but will require special parsing. The example that corresponds to JAWS item above looks like:

```text
0L02-7 GMES={
  "action": "GMES", 
  "location": ["Injector"], 
  "maskedby": null, 
  "source": {"org.jlab.jaws.entity.EPICSSource": {"pv": "R027GMES"}},
  "pv": "R027GMES",
  "screencommand": null
}
```

### Jaws API

See the source code at https://github.com/JeffersonLab/jaws-web/tree/main/src/main/java/org/jlab/jaws/presentation/ajax


